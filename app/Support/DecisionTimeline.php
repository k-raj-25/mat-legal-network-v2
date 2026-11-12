<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\ProfileChangeLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the admin "Decision history" for one lawyer: review decisions from the audit log, plus one
 * collapsible entry per round of edits the lawyer made after changes were requested.
 *
 * Edits made while the profile is in "changes requested" are grouped until the next review decision,
 * so the profile save, the resubmit and any document uploads in the same round show as one entry.
 */
class DecisionTimeline
{
    private const REVIEW_ACTIONS = ['lawyer.approved', 'lawyer.rejected', 'lawyer.suspended'];

    /**
     * @param  Collection<int, AuditLog>  $audit
     * @param  Collection<int, ProfileChangeLog>  $changeLogs
     * @return list<array{type: string, label: string, at: Carbon, by: ?string, reason?: ?string, changes?: array, resubmitted?: bool, count?: int}>
     */
    public static function build(Collection $audit, Collection $changeLogs): array
    {
        $events = $audit->map(fn (AuditLog $a) => ['audit', $a])
            ->concat($changeLogs->map(fn (ProfileChangeLog $c) => ['change', $c]))
            ->sortBy(fn ($e) => [$e[1]->created_at->getTimestamp(), $e[0] === 'audit' ? 0 : 1, $e[1]->id])
            ->values();

        $items = [];
        $round = null; // edits collected since the last "changes requested"
        $collecting = false;

        foreach ($events as [$type, $row]) {
            if ($type === 'audit') {
                if (in_array($row->action, self::REVIEW_ACTIONS, true)) {
                    if ($round) {
                        $items[] = $round;
                    }
                    $round = null;
                    $collecting = $row->action === 'lawyer.rejected';
                }
                $items[] = ['type' => 'decision', 'label' => $row->label(), 'at' => $row->created_at, 'by' => $row->user?->name, 'reason' => $row->meta['reason'] ?? null];

                continue;
            }

            if (! $collecting || ! is_array($row->changes)) {
                continue;
            }

            $round ??= ['type' => 'changes', 'label' => '', 'at' => $row->created_at, 'by' => $row->user?->name, 'changes' => [], 'resubmitted' => false];
            $round['at'] = $row->created_at;
            foreach ($row->changes as $field => $c) {
                if ($field === 'status') {
                    $round['resubmitted'] = true;
                }
                if (! isset($round['changes'][$field])) {
                    $round['changes'][$field] = $c;
                } elseif ($field === 'documents') {
                    $round['changes'][$field]['new'] .= ', '.$c['new'];
                } else {
                    $round['changes'][$field]['new'] = $c['new'];
                }
            }
        }
        if ($round) {
            $items[] = $round;
        }

        foreach ($items as &$item) {
            if ($item['type'] === 'changes') {
                $item['label'] = $item['resubmitted'] ? 'Lawyer made the requested changes' : 'Lawyer is editing (not resubmitted yet)';
                // Drop fields that were edited and then put back, and list the status line last.
                $item['changes'] = array_filter($item['changes'], fn ($c, $f) => $f === 'documents' || (string) $c['old'] !== (string) $c['new'], ARRAY_FILTER_USE_BOTH);
                if (isset($item['changes']['status'])) {
                    $status = $item['changes']['status'];
                    unset($item['changes']['status']);
                    $item['changes']['status'] = $status;
                }
                $item['count'] = count($item['changes']) - (isset($item['changes']['status']) ? 1 : 0);
            }
        }
        unset($item);

        return array_reverse($items); // newest first
    }
}
