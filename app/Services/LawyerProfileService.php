<?php

namespace App\Services;

use App\Enums\LawyerStatus;
use App\Enums\Role;
use App\Models\City;
use App\Models\Country;
use App\Models\LawyerProfile;
use App\Models\ProfileChangeLog;
use App\Models\State;
use App\Models\User;
use App\Notifications\LawyerRegistrationReceived;
use App\Notifications\NewLawyerAwaitingReview;
use App\Rules\PhoneNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class LawyerProfileService
{
    public const FIELD_LABELS = [
        'full_name' => 'Full name', 'gender' => 'Gender', 'designation' => 'Designation', 'firm_name' => 'Firm / chamber',
        'practice_since' => 'Practising since', 'education' => 'Education', 'bio' => 'About', 'consultation_fee' => 'Consultation fee',
        'offers_online_consultation' => 'Online consultation', 'state_id' => 'State', 'city_id' => 'City',
        'phone' => 'Phone', 'alternate_phone' => 'Alternate phone', 'office_address' => 'Office address', 'pincode' => 'PIN code',
        'enrolment_number' => 'Enrolment number', 'bar_council' => 'Bar Council', 'practice_areas' => 'Practice areas',
        'languages' => 'Languages', 'courts' => 'Courts', 'photo' => 'Profile photo', 'documents' => 'Documents',
    ];

    /** Create the user + pending profile + documents in one transaction. */
    public function register(array $data, array $files): LawyerProfile
    {
        $stored = [];

        try {
            $lawyer = DB::transaction(function () use ($data, $files, &$stored) {
                $user = new User(['name' => $data['full_name'], 'email' => $data['email'], 'password' => $data['password']]);
                $user->role = Role::Lawyer;
                $user->save();

                $lawyer = new LawyerProfile($this->profileAttributes($data));
                $lawyer->user_id = $user->id;
                $lawyer->country_id = Country::default()->id;
                $lawyer->status = LawyerStatus::Pending;
                $lawyer->submitted_at = now();
                if (! empty($files['photo'])) {
                    $lawyer->photo_path = $stored[] = $this->storePhoto($files['photo']);
                }
                $lawyer->save();

                $this->syncRelations($lawyer, $data);

                foreach (['enrolment_certificate', 'id_proof', 'other'] as $type) {
                    if (! empty($files[$type])) {
                        $stored[] = $this->addDocument($lawyer, $type, $files[$type])->path;
                    }
                }

                return $lawyer;
            });
        } catch (\Throwable $e) {
            // Don't leave orphaned uploads behind if the DB write failed.
            foreach ($stored as $path) {
                Storage::disk('public')->delete($path);
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }

        $lawyer->user->notify(new LawyerRegistrationReceived($lawyer));
        if ($to = config('mat.admin.notify_email')) {
            Notification::route('mail', $to)->notify(new NewLawyerAwaitingReview($lawyer));
        }

        return $lawyer;
    }

    /**
     * Update a profile. Changes go live immediately and are recorded in the change log for the admin.
     * If the profile was rejected, saving with $resubmit=true sends it back to the review queue.
     */
    public function update(LawyerProfile $lawyer, array $data, array $files = [], bool $resubmit = false): ?ProfileChangeLog
    {
        return DB::transaction(function () use ($lawyer, $data, $files, $resubmit) {
            $before = $this->snapshot($lawyer);

            $lawyer->fill($this->profileAttributes($data));
            if (array_key_exists('full_name', $data)) {
                $lawyer->user->forceFill(['name' => $data['full_name']])->save();
            }

            $photoChanged = false;
            if (! empty($files['photo'])) {
                $old = $lawyer->photo_path;
                $lawyer->photo_path = $this->storePhoto($files['photo']);
                DB::afterCommit(fn () => $old && Storage::disk('public')->delete($old));
                $photoChanged = true;
            } elseif (! empty($data['remove_photo']) && $lawyer->photo_path) {
                $old = $lawyer->photo_path;
                $lawyer->photo_path = null;
                DB::afterCommit(fn () => Storage::disk('public')->delete($old));
                $photoChanged = true;
            }

            if ($resubmit && $lawyer->status === LawyerStatus::Rejected) {
                $lawyer->status = LawyerStatus::Pending;
                $lawyer->submitted_at = now();
            }
            $lawyer->save();
            $this->syncRelations($lawyer, $data);

            $changes = $this->diff($before, $this->snapshot($lawyer->fresh()));
            if ($photoChanged) {
                $changes['photo'] = ['old' => $before['photo'] ? 'Previous photo' : '—', 'new' => $lawyer->photo_path ? 'New photo uploaded' : 'Removed'];
            }
            if ($resubmit) {
                $changes['status'] = ['old' => 'Changes requested', 'new' => 'Resubmitted for review'];
            }

            return $changes ? $this->log($lawyer, $changes) : null;
        });
    }

    public function addDocument(LawyerProfile $lawyer, string $type, UploadedFile $file, bool $log = false)
    {
        $path = $file->store('lawyer-documents/'.$lawyer->id, 'local');
        $doc = $lawyer->documents()->create([
            'type' => $type,
            'path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);
        if ($log) {
            $this->log($lawyer, ['documents' => ['old' => '—', 'new' => 'Uploaded: '.$doc->typeLabel()]]);
        }

        return $doc;
    }

    public function log(LawyerProfile $lawyer, array $changes): ProfileChangeLog
    {
        return ProfileChangeLog::create([
            'lawyer_profile_id' => $lawyer->id,
            'user_id' => auth()->id(),
            'changes' => $changes,
            'ip_address' => request()?->ip(),
        ]);
    }

    private function profileAttributes(array $data): array
    {
        $attrs = Arr::only($data, LawyerProfile::EDITABLE_FIELDS);
        foreach (['phone', 'alternate_phone'] as $p) {
            if (array_key_exists($p, $attrs)) {
                $attrs[$p] = PhoneNumber::normalize($attrs[$p]);
            }
        }
        if (array_key_exists('offers_online_consultation', $data)) {
            $attrs['offers_online_consultation'] = (bool) $data['offers_online_consultation'];
        }

        return $attrs;
    }

    private function syncRelations(LawyerProfile $lawyer, array $data): void
    {
        foreach (['practice_areas' => 'practiceAreas', 'languages' => 'languages', 'courts' => 'courts'] as $key => $rel) {
            if (array_key_exists($key, $data)) {
                $lawyer->{$rel}()->sync(array_map('intval', (array) $data[$key]));
            }
        }
    }

    private function storePhoto(UploadedFile $file): string
    {
        return $file->store('lawyer-photos', 'public');
    }

    /** Human-readable snapshot of everything we diff. */
    private function snapshot(LawyerProfile $l): array
    {
        $l->loadMissing(['practiceAreas', 'languages', 'courts']);
        $snap = [];
        foreach (LawyerProfile::EDITABLE_FIELDS as $f) {
            $v = $l->getAttribute($f);
            $snap[$f] = match ($f) {
                'state_id' => State::find($v)?->name,
                'city_id' => City::find($v)?->name,
                'offers_online_consultation' => $v ? 'Yes' : 'No',
                default => $v === null || $v === '' ? null : (string) $v,
            };
        }
        $snap['practice_areas'] = $l->practiceAreas->pluck('name')->sort()->implode(', ');
        $snap['languages'] = $l->languages->pluck('name')->sort()->implode(', ');
        $snap['courts'] = $l->courts->pluck('name')->sort()->implode(', ');
        $snap['photo'] = $l->photo_path;

        return $snap;
    }

    private function diff(array $before, array $after): array
    {
        $changes = [];
        foreach ($after as $k => $new) {
            if ($k === 'photo') {
                continue;
            }
            $old = $before[$k] ?? null;
            if ((string) $old !== (string) $new) {
                $changes[$k] = ['old' => $old ?? '—', 'new' => $new ?? '—'];
            }
        }

        return $changes;
    }
}
