#!/usr/bin/env python3
"""Builds the site's illustration set (public/images/illustrations/*.svg).

All artwork is original and drawn from the primitives below in the brand
palette, so there is nothing to licence. Run:  python3 scripts/illustrations/build.py
"""
import math
import os
import re

OUT = os.path.join(os.path.dirname(__file__), '..', '..', 'public', 'images', 'illustrations')

INK, INK2 = '#0b1324', '#1a2540'
T50, T100, T200, T300, T400, T500, T600, T700 = '#ecfdf8', '#d1faef', '#a5f3dd', '#6ee7c8', '#2dd4b0', '#14b8a0', '#0a7f78', '#0b6b66'
C50, C100, C200, C300, C400, C500, C600 = '#fff4f1', '#ffe4de', '#ffc9bd', '#ffa594', '#ff7a64', '#ff5a45', '#d93a26'
A100, A200, A300, A400 = '#fef3c7', '#fde68a', '#fcd34d', '#fbbf24'
V100, V200, V400, V500, V600 = '#ede9fe', '#ddd6fe', '#a78bfa', '#8b5cf6', '#4f46e5'
S50, S100, S200, S300, S400 = '#f8fafc', '#f1f5f9', '#e2e8f0', '#cbd5e1', '#94a3b8'
W = '#ffffff'
GREEN = '#128c4a'
SKIN = ['#e9b48a', '#c98d62', '#9c6644']
HAIR = '#2a1d17'

_uid = [0]


def n(v):
    return ('%.1f' % v).rstrip('0').rstrip('.')


def attrs(kw):
    return ''.join(' %s="%s"' % (k.rstrip('_').replace('_', '-'), v) for k, v in kw.items() if v is not None)


def rr(x, y, w, h, r, fill, **kw):
    return '<rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="%s"%s/>' % (n(x), n(y), n(w), n(h), n(r), fill, attrs(kw))


def circ(cx, cy, r, fill, **kw):
    return '<circle cx="%s" cy="%s" r="%s" fill="%s"%s/>' % (n(cx), n(cy), n(r), fill, attrs(kw))


def path(d, fill='none', **kw):
    return '<path d="%s" fill="%s"%s/>' % (d, fill, attrs(kw))


def line(d, stroke, width, **kw):
    return path(d, 'none', stroke=stroke, stroke_width=width, stroke_linecap='round', stroke_linejoin='round', **kw)


def g(content, x=0, y=0, s=1, rot=0, **kw):
    t = 'translate(%s %s)' % (n(x), n(y))
    if rot:
        t += ' rotate(%s)' % n(rot)
    if s != 1:
        t += ' scale(%s)' % n(s)
    return '<g transform="%s"%s>%s</g>' % (t, attrs(kw), ''.join(content) if isinstance(content, list) else content)


def grad(c1, c2, angle=45):
    _uid[0] += 1
    i = 'g%d' % _uid[0]
    a = math.radians(angle)
    x2, y2 = 0.5 + math.cos(a) / 2, 0.5 + math.sin(a) / 2
    d = '<linearGradient id="%s" x1="%s" y1="%s" x2="%s" y2="%s"><stop offset="0" stop-color="%s"/><stop offset="1" stop-color="%s"/></linearGradient>' % (
        i, n(1 - x2), n(1 - y2), n(x2), n(y2), c1, c2)
    return d, 'url(#%s)' % i


# ---------------------------------------------------------------- decoration
def dots(x, y, cols, rows, gap=16, r=2.2, fill=INK, op=0.14):
    return '<g fill="%s" opacity="%s">%s</g>' % (fill, op, ''.join(
        '<circle cx="%s" cy="%s" r="%s"/>' % (n(x + c * gap), n(y + r_ * gap), r) for r_ in range(rows) for c in range(cols)))


def spark(x, y, s, fill):
    return path('M0,-%s C1.6,-3 3,-1.6 %s,0 C3,1.6 1.6,3 0,%s C-1.6,3 -3,1.6 -%s,0 C-3,-1.6 -1.6,-3 0,-%s Z' % (s, s, s, s, s), fill, transform='translate(%s %s)' % (n(x), n(y)))


def plus(x, y, s, stroke, w=4):
    return line('M%s,%s h%s M%s,%s v%s' % (n(x - s), n(y), n(2 * s), n(x), n(y - s), n(2 * s)), stroke, w)


def ring(x, y, r, stroke, w=4, **kw):
    return circ(x, y, r, 'none', stroke=stroke, stroke_width=w, **kw)


def blob(cx, cy, w, h, rot, fill, r=44, **kw):
    return g(rr(-w / 2, -h / 2, w, h, r, fill, **kw), cx, cy, rot=rot)


def shadow(shape_fn, dx=0, dy=10, op=0.10):
    return '<g opacity="%s" transform="translate(%s %s)">%s</g>' % (op, dx, dy, shape_fn(INK))


# ------------------------------------------------------------------- objects
def scales(x, y, s=1, tilt=-7, body=INK, pan=A300, accent=C500):
    a = math.radians(tilt)
    arm = 118
    ends = [(-arm * math.cos(a), -84 - arm * math.sin(a) * -1 * -1), (arm * math.cos(a), -84 + arm * math.sin(a))]
    ends[0] = (-arm * math.cos(a), -84 - arm * math.sin(a))
    o = [rr(-58, 150, 116, 16, 8, body), rr(-34, 136, 68, 16, 8, body), rr(-7, -92, 14, 232, 7, body)]
    o.append(g(rr(-arm - 10, -6, 2 * arm + 20, 12, 6, body), 0, -84, rot=tilt))
    o.append(circ(0, -84, 15, accent))
    o.append(circ(0, -84, 6, W))
    o.append(circ(0, -108, 9, body))
    for ex, ey in ends:
        o.append(line('M%s,%s L%s,%s M%s,%s L%s,%s' % (n(ex), n(ey), n(ex - 44), n(ey + 92), n(ex), n(ey), n(ex + 44), n(ey + 92)), body, 3.5))
        o.append(path('M%s,%s h108 a54,34 0 0 1 -108,0 Z' % (n(ex - 54), n(ey + 92)), pan))
        o.append(rr(ex - 58, ey + 88, 116, 9, 4.5, body))
        o.append(circ(ex, ey, 6, body))
    return g(o, x, y, s)


def courthouse(x, y, s=1, cols=4, wall=W, col=S100, roof=INK, accent=A300, step=S200, door=T600):
    w = 60 + cols * 44
    o = []
    o.append(path('M%s,-150 L0,-222 L%s,-150 Z' % (n(-w / 2 - 14), n(w / 2 + 14)), roof))
    o.append(path('M%s,-158 L0,-204 L%s,-158 Z' % (n(-w / 2 + 28), n(w / 2 - 28)), INK2))
    o.append(circ(0, -174, 11, accent))
    o.append(rr(-w / 2 - 6, -150, w + 12, 20, 4, wall))
    o.append(rr(-w / 2, -130, w, 8, 0, step))
    o.append(rr(-w / 2 + 8, -122, w - 16, 122, 0, wall))
    gap = (w - 40) / (cols - 1) if cols > 1 else 0
    for i in range(cols):
        cx = -w / 2 + 20 + i * gap
        o.append(rr(cx - 11, -116, 22, 110, 3, col))
        o.append(rr(cx - 15, -122, 30, 10, 3, step))
        o.append(rr(cx - 15, -10, 30, 10, 3, step))
        o.append(rr(cx - 4, -108, 3, 94, 1.5, step))
    if cols % 2 == 0:
        o.append(path('M-17,0 V-58 a17,17 0 0 1 34,0 V0 Z', door))
    o.append(rr(-w / 2 - 10, 0, w + 20, 12, 3, step))
    o.append(rr(-w / 2 - 26, 12, w + 52, 12, 3, S300))
    o.append(rr(-w / 2 - 42, 24, w + 84, 12, 3, step))
    return g(o, x, y, s)


def gavel(x, y, s=1, rot=-35, head=INK, band=A300, handle=C500):
    o = [rr(-8, 10, 16, 150, 8, handle), rr(-58, -30, 116, 56, 12, head), rr(-58, -30, 20, 56, 8, band), rr(38, -30, 20, 56, 8, band)]
    return g(o, x, y, s, rot)


def block(x, y, s=1, fill=INK, top=INK2):
    return g([rr(-70, 0, 140, 22, 8, fill), rr(-56, -12, 112, 16, 7, top)], x, y, s)


def document(x, y, w=150, h=190, rot=0, s=1, accent=C500, lines=5, seal=True, paper=W, head=True):
    o = [rr(-w / 2 + 6, -h / 2 + 10, w, h, 14, INK, opacity=0.10), rr(-w / 2, -h / 2, w, h, 14, paper)]
    yy = -h / 2 + 22
    if head:
        o.append(rr(-w / 2 + 18, yy, w * 0.45, 10, 5, INK))
        yy += 26
    for i in range(lines):
        o.append(rr(-w / 2 + 18, yy, (w - 36) * (0.62 if i == lines - 1 else (1 if i % 2 == 0 else 0.86)), 7, 3.5, S200))
        yy += 17
    if seal:
        o.append(circ(w / 2 - 34, h / 2 - 34, 18, accent))
        o.append(line('M%s,%s l6,6 l11,-12' % (n(w / 2 - 42), n(h / 2 - 34)), W, 4))
        o.append(line('M%s,%s c8,-12 14,8 22,-2 s10,-8 16,0' % (n(-w / 2 + 18), n(h / 2 - 28)), INK, 3))
    return g(o, x, y, s, rot)


def shield(x, y, s=1, fill=T500, mark=W, rot=0, check=True):
    o = [path('M0,-62 L52,-42 V4 C52,40 26,62 0,74 C-26,62 -52,40 -52,4 V-42 Z', fill)]
    o.append(path('M0,-62 L52,-42 V4 C52,40 26,62 0,74 Z', INK, opacity=0.10))
    if check:
        o.append(line('M-22,4 l15,15 l30,-33', mark, 10))
    return g(o, x, y, s, rot)


def badge(x, y, r=20, fill=T500, mark=W):
    return circ(x, y, r, fill) + line('M%s,%s l%s,%s l%s,%s' % (n(x - r * .42), n(y + r * .02), n(r * .3), n(r * .3), n(r * .56), n(-r * .62)), mark, r * .2)


def bubble(x, y, w, h, fill, tail='l', lines=2, lc=W, lop=0.85):
    o = [rr(0, 0, w, h, min(18, h / 2), fill)]
    o.append(path('M18,%s l-4,16 l20,-16 Z' % n(h - 2), fill) if tail == 'l' else path('M%s,%s l4,16 l-20,-16 Z' % (n(w - 18), n(h - 2)), fill))
    for i in range(lines):
        o.append(rr(16, 14 + i * 14, (w - 32) * (1 if i < lines - 1 or lines == 1 else 0.6), 6, 3, lc, opacity=lop))
    return g(o, x, y)


def phone(x, y, s=1, rot=0, screen=None, body=INK):
    o = [rr(-78, -150, 156, 300, 30, INK, opacity=0.10, transform='translate(8 12)'), rr(-78, -150, 156, 300, 30, body), rr(-68, -140, 136, 280, 22, W),
         rr(-24, -134, 48, 10, 5, body)]
    if screen:
        o.append(screen)
    return g(o, x, y, s, rot)


def pin(x, y, s=1, fill=C500, dot=W):
    return g([path('M0,0 C-30,-36 -38,-52 -38,-68 A38,38 0 1 1 38,-68 C38,-52 30,-36 0,0 Z', fill), circ(0, -68, 14, dot)], x, y, s)


def building(x, base, w, h, fill, win=W, wop=0.55, cols=3, top=None):
    o = [rr(x, base - h, w, h + 8, 8, fill)]
    if top == 'ant':
        o.append(rr(x + w / 2 - 2, base - h - 24, 4, 26, 2, fill))
    if top == 'dome':
        o.append(path('M%s,%s a%s,%s 0 0 1 %s,0 Z' % (n(x + w * .2), n(base - h + 1), n(w * .3), n(w * .3), n(w * .6)), fill))
        o.append(rr(x + w / 2 - 2, base - h - w * .3 - 14, 4, 16, 2, fill))
    cw = (w - 16) / cols
    rows = int((h - 18) / 22)
    for r_ in range(rows):
        for c in range(cols):
            if (r_ * 7 + c * 3 + int(x)) % 5 == 0:
                continue
            o.append(rr(x + 8 + c * cw + cw * .18, base - h + 14 + r_ * 22, cw * .64, 10, 3, win, opacity=wop))
    return ''.join(o)


def tree(x, base, s=1, fill=T500, trunk=T700):
    return g([rr(-3, -24, 6, 26, 3, trunk), circ(0, -40, 20, fill), circ(-12, -28, 13, fill), circ(12, -28, 13, fill)], x, base, s)


def book(x, y, w, h, fill, band=W, rot=0):
    return g([rr(0, 0, w, h, 5, fill), rr(w * .12, 0, 8, h, 0, band, opacity=0.7), rr(w * .78, 0, 4, h, 0, band, opacity=0.45)], x, y, rot=rot)


def briefcase(x, y, s=1, fill=INK, strap=A300, rot=0):
    o = [path('M-24,-44 v-12 a10,10 0 0 1 10,-10 h28 a10,10 0 0 1 10,10 v12', 'none', stroke=fill, stroke_width=10, stroke_linejoin='round'),
         rr(-70, -46, 140, 96, 16, fill), rr(-70, -46, 140, 40, 16, INK2 if fill == INK else W, opacity=1 if fill == INK else .18), rr(-70, -18, 140, 14, 0, fill),
         rr(-13, -22, 26, 22, 6, strap)]
    return g(o, x, y, s, rot)


def magnifier(x, y, s=1, rot=0, rim=INK, glass=T100, handle=C500):
    return g([g(rr(-9, 52, 18, 76, 9, handle), rot=-42), circ(0, 0, 58, rim), circ(0, 0, 44, glass), path('M-26,-10 a30,30 0 0 1 26,-22', 'none', stroke=W, stroke_width=7, stroke_linecap='round', opacity=0.9)], x, y, s, rot)


def advocate(x, y, s=1, skin=SKIN[1], hair='short', coat=INK, bg=None):
    o = []
    if bg:
        o.append(circ(0, 30, 96, bg))
    if hair == 'long':
        o.append(path('M-36,-6 C-40,-52 40,-52 36,-6 L40,50 C20,58 -20,58 -40,50 Z', HAIR))
    o.append(path('M-84,126 C-84,66 -50,46 0,46 C50,46 84,66 84,126 Z', coat))
    o.append(path('M-20,46 L0,74 L20,46 Z', W))
    o.append(rr(-13, 18, 26, 36, 10, skin))
    o.append(path('M-9,58 l-3,34 h10 l2,-34 Z M9,58 l3,34 h-10 l-2,-34 Z', W))
    o.append('<ellipse cx="0" cy="-6" rx="30" ry="33" fill="%s"/>' % skin)
    if hair == 'long':
        o.append(path('M-31,-8 C-34,-48 34,-48 31,-8 C22,-30 2,-34 -12,-30 C-22,-27 -28,-18 -31,-8 Z', HAIR))
    elif hair == 'bun':
        o.append(circ(0, -44, 13, HAIR))
        o.append(path('M-31,-6 C-34,-46 34,-46 31,-6 C24,-26 -24,-26 -31,-6 Z', HAIR))
    else:
        o.append(path('M-31,-10 C-36,-50 36,-50 31,-10 C26,-24 12,-30 -6,-30 C-18,-30 -27,-22 -31,-10 Z', HAIR))
    return g(o, x, y, s)


def person(x, y, s=1, skin=SKIN[0], top=T500, hair='short'):
    return advocate(x, y, s, skin, hair, coat=top).replace(path('M-9,58 l-3,34 h10 l2,-34 Z M9,58 l3,34 h-10 l-2,-34 Z', W), '')


def profile_card(x, y, w=260, s=1, rot=0, avatar=None, chips=((C100, C500), (A100, A400), (T100, T600)), buttons=True, tone=V500):
    h = 176 if buttons else 124
    d, fill = grad(tone, V600)
    o = ['<defs>%s</defs>' % d, rr(6, 12, w, h, 24, INK, opacity=0.10), rr(0, 0, w, h, 24, W), rr(20, 20, 54, 54, 16, fill)]
    if avatar:
        o.append('<clipPath id="c%s"><rect x="20" y="20" width="54" height="54" rx="16"/></clipPath>' % _uid[0])
        o.append('<g clip-path="url(#c%s)">%s</g>' % (_uid[0], avatar))
    o += [rr(88, 26, w * .42, 13, 6.5, INK), rr(88, 50, w * .32, 9, 4.5, S300), badge(w - 38, 46, 15, T500)]
    cx = 20
    for bg_, _fg in chips:
        o.append(rr(cx, 90, 62, 20, 10, bg_))
        o.append(rr(cx + 12, 97, 38, 6, 3, _fg, opacity=0.8))
        cx += 70
    if buttons:
        bw = (w - 50) / 2
        o += [rr(20, 126, bw, 32, 16, T600), rr(30 + bw, 126, bw, 32, 16, GREEN), rr(20 + bw / 2 - 22, 139, 44, 6, 3, W, opacity=0.9), rr(30 + bw * 1.5 - 22, 139, 44, 6, 3, W, opacity=0.9)]
    return g(o, x, y, s, rot)


def house(x, y, s=1, wall=W, roof=C500, door=INK, win=A300):
    o = [rr(-62, -70, 124, 100, 6, wall), path('M-82,-64 L0,-128 L82,-64 Z', roof), rr(36, -122, 16, 36, 3, roof), rr(-14, -22, 28, 52, 6, door),
         rr(-50, -52, 26, 26, 5, win), rr(24, -52, 26, 26, 5, win), circ(7, 6, 2.5, win)]
    return g(o, x, y, s)


def heart(x, y, s=1, fill=C500, rot=0):
    return g(path('M0,26 C-34,2 -40,-22 -22,-34 C-10,-42 0,-34 0,-24 C0,-34 10,-42 22,-34 C40,-22 34,2 0,26 Z', fill), x, y, s, rot)


def coin(x, y, r=22, fill=A300, rim=A400):
    return circ(x, y, r, rim) + circ(x, y, r * .76, fill) + rr(x - r * .3, y - r * .32, r * .6, r * .14, r * .07, rim) + rr(x - r * .3, y - r * .05, r * .6, r * .14, r * .07, rim) + \
        line('M%s,%s l%s,%s' % (n(x - r * .1), n(y + .12 * r), n(r * .32), n(r * .34)), rim, r * .14)


def cheque(x, y, w=230, h=112, rot=0, s=1):
    o = [rr(6, 10, w, h, 12, INK, opacity=0.10), rr(0, 0, w, h, 12, W), rr(0, 0, w, 24, 12, T100), rr(0, 14, w, 10, 0, T100), rr(16, 8, 60, 8, 4, T600),
         rr(16, 40, w * .5, 8, 4, S200), rr(16, 58, w * .36, 8, 4, S200), rr(w - 78, 38, 62, 28, 8, A100), rr(w - 66, 48, 38, 8, 4, A400),
         line('M%s,%s c8,-12 14,8 22,-2 s10,-8 18,0' % (n(w - 86), n(h - 18)), INK, 3), rr(16, h - 22, 84, 6, 3, S300)]
    return g(o, x, y, s, rot)


def bag(x, y, s=1, fill=C500, rot=0):
    o = [path('M-26,-40 v-14 a26,26 0 0 1 52,0 v14', 'none', stroke=INK, stroke_width=8, stroke_linecap='round'), path('M-56,-44 h112 l10,112 a12,12 0 0 1 -12,12 h-108 a12,12 0 0 1 -12,-12 Z', fill),
         path('M-56,-44 h112 l2,22 h-116 Z', INK, opacity=0.14), circ(-26, -22, 6, INK), circ(26, -22, 6, INK)]
    return g(o, x, y, s, rot)


def megaphone(x, y, s=1, rot=-12):
    o = [path('M-60,-22 L40,-62 V62 L-60,22 Z', C500), rr(-84, -24, 34, 48, 10, INK), rr(36, -70, 18, 140, 9, INK), path('M-52,24 l14,52 h24 l-12,-44 Z', INK2),
         line('M74,-34 l22,-12 M78,0 h26 M74,34 l22,12', A400, 7)]
    return g(o, x, y, s, rot)


def newspaper(x, y, w=210, h=250, rot=0, s=1):
    o = [rr(8, 12, w, h, 14, INK, opacity=0.10), rr(0, 0, w, h, 14, W), rr(18, 20, w - 36, 16, 6, INK), rr(18, 48, w - 36, 4, 2, S200)]
    d, fill = grad(T400, T600)
    o += ['<defs>%s</defs>' % d, rr(18, 64, w * .46, 74, 10, fill), circ(18 + w * .14, 88, 10, W, opacity=.8), path('M18,138 v-14 l26,-22 l22,18 l16,-10 l%s,22 v6 Z' % n(w * .46 - 64), W, opacity=.35)]
    for i in range(5):
        o.append(rr(30 + w * .46, 66 + i * 15, (w * .54 - 48) * (0.7 if i == 4 else 1), 7, 3.5, S200))
    for i in range(5):
        o.append(rr(18, 154 + i * 16, (w - 36) * (0.55 if i == 4 else 1), 7, 3.5, S200))
    return g(o, x, y, s, rot)


def open_book(x, y, s=1, cover=INK, page=W, mark=C500):
    o = [path('M-120,-52 h110 a10,10 0 0 1 10,10 v96 h-120 a10,10 0 0 1 -10,-10 v-86 a10,10 0 0 1 10,-10 Z', cover, transform='translate(0 10)'),
         path('M120,-52 h-110 a10,10 0 0 0 -10,10 v96 h120 a10,10 0 0 0 10,-10 v-86 a10,10 0 0 0 -10,-10 Z', cover, transform='translate(0 10)'),
         path('M-110,-54 C-70,-64 -30,-60 0,-44 V52 C-30,38 -70,34 -110,44 Z', page), path('M110,-54 C70,-64 30,-60 0,-44 V52 C30,38 70,34 110,44 Z', S50),
         path('M60,-58 v54 l12,-10 l12,10 v-56 Z', mark)]
    for i in range(4):
        o.append(line('M-94,%s C-66,%s -40,%s -16,%s' % (n(-34 + i * 18), n(-42 + i * 18), n(-40 + i * 18), n(-28 + i * 18)), S300, 4))
    for i in range(3):
        o.append(line('M16,%s C28,%s 38,%s 48,%s' % (n(-28 + i * 18), n(-34 + i * 18), n(-38 + i * 18), n(-40 + i * 18)), S300, 4))
    return g(o, x, y, s)


def headset(x, y, s=1):
    o = [path('M-52,10 V-6 a52,52 0 0 1 104,0 V10', 'none', stroke=INK, stroke_width=11, stroke_linecap='round'), rr(-66, 0, 28, 50, 12, C500), rr(38, 0, 28, 50, 12, C500),
         path('M52,50 v8 a22,22 0 0 1 -22,22 h-14', 'none', stroke=INK, stroke_width=7, stroke_linecap='round'), rr(0, 70, 22, 16, 8, INK)]
    return g(o, x, y, s)


def lock(x, y, s=1, body=A300, shackle=INK):
    return g([path('M-22,-8 v-20 a22,22 0 0 1 44,0 v20', 'none', stroke=shackle, stroke_width=11, stroke_linecap='round'), rr(-36, -10, 72, 60, 14, body), circ(0, 14, 8, shackle), rr(-3.5, 14, 7, 18, 3.5, shackle)], x, y, s)


def envelope(x, y, s=1, rot=0, fill=W, flap=C400):
    return g([rr(-56, -34, 118, 78, 12, INK, opacity=.1), rr(-60, -40, 120, 80, 12, fill), path('M-60,-30 a10,10 0 0 1 10,-10 h100 a10,10 0 0 1 10,10 L0,14 Z', flap)], x, y, s, rot)


def skyline(x, base, tone=(T600, T500, T400, C400, V500, INK2), s=1):
    specs = [(0, 54, 120, 0, None), (60, 70, 190, 2, 'ant'), (138, 58, 96, 3, None), (204, 86, 150, 5, 'dome'), (298, 50, 220, 1, 'ant'), (356, 74, 130, 4, None), (438, 56, 170, 0, None)]
    return g([building(bx, 0, bw, bh, tone[ti], top=top) for bx, bw, bh, ti, top in specs], x, base, s)


# -------------------------------------------------------------------- output
def svg(name, w, h, body, title=None, defs=''):
    doc = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d"%s>%s%s%s</svg>' % (
        w, h, w, h, ' role="img"' if title else '', '<title>%s</title>' % title if title else '', '<defs>%s</defs>' % defs if defs else '', ''.join(body) if isinstance(body, list) else body)
    doc = re.sub(r'\s+', ' ', doc).replace('> <', '><')
    os.makedirs(OUT, exist_ok=True)
    with open(os.path.join(OUT, name + '.svg'), 'w') as f:
        f.write(doc + '\n')
    return len(doc)


def panel(w, h, c1, c2, angle=35, r=0):
    d, fill = grad(c1, c2, angle)
    return d, rr(0, 0, w, h, r, fill)


# -------------------------------------------------------------------- scenes
def hero_about():
    d1, f1 = grad(T400, T600)
    d2, f2 = grad(C300, C500)
    b = [blob(330, 290, 420, 400, 7, f1, 56), blob(316, 296, 420, 400, -4, f2, 56, opacity=.92), dots(40, 60, 6, 5), dots(500, 430, 6, 4)]
    b += [courthouse(322, 430, 1.05, cols=4)]
    b += [g(scales(0, 0, .5, tilt=-6, body=INK, pan=A300), 512, 190), rr(444, 266, 136, 16, 8, INK, opacity=.0)]
    b += [profile_card(18, 356, 236, .92, rot=-5, avatar=advocate(47, 52, .34, SKIN[1], 'long'), buttons=False)]
    b += [g([rr(0, 0, 176, 56, 20, INK), badge(30, 28, 15, T400, INK), rr(56, 17, 96, 9, 4.5, W), rr(56, 33, 64, 7, 3.5, S400)], 420, 430)]
    b += [spark(96, 250, 16, A300), spark(566, 96, 12, W), plus(586, 330, 9, INK), circ(70, 318, 8, C500), ring(250, 66, 14, INK, 5)]
    svg('hero-about', 640, 560, b, defs=d1 + d2)


def hero_lawyers():
    d1, f1 = grad(C300, C500)
    d2, f2 = grad(A200, A400)
    b = [blob(326, 286, 400, 420, -6, f2, 56), blob(318, 290, 400, 420, 5, f1, 56, opacity=.94), dots(36, 420, 6, 5), dots(520, 50, 5, 5)]
    b += [g([rr(8, 14, 300, 372, 30, INK, opacity=.12), rr(0, 0, 300, 372, 30, W), rr(0, 0, 300, 118, 30, T100), rr(0, 60, 300, 58, 0, T100),
             advocate(150, 96, .95, SKIN[1], 'short'), rr(0, 208, 300, 20, 0, W),
             rr(60, 232, 180, 16, 8, INK), rr(92, 258, 116, 10, 5, S300),
             rr(34, 286, 70, 22, 11, C100), rr(46, 294, 46, 6, 3, C500), rr(114, 286, 70, 22, 11, A100), rr(126, 294, 46, 6, 3, A400), rr(194, 286, 72, 22, 11, T100), rr(206, 294, 48, 6, 3, T600),
             rr(34, 324, 232, 30, 15, T600), rr(116, 336, 68, 6, 3, W, opacity=.9)], 170, 92)]
    b += [g([circ(0, 0, 40, W), badge(0, 0, 30, T500)], 458, 112)]
    b += [g([rr(4, 8, 190, 64, 20, INK, opacity=.12), rr(0, 0, 190, 64, 20, INK), circ(34, 32, 18, C500), line('M26,32 h16 M34,24 v16', W, 4), rr(64, 20, 96, 9, 4.5, W), rr(64, 38, 66, 7, 3.5, S400)], 28, 150, rot=-4)]
    b += [g([rr(4, 8, 178, 58, 18, INK, opacity=.10), rr(0, 0, 178, 58, 18, W), lock(30, 28, .38), rr(58, 18, 92, 9, 4.5, INK), rr(58, 35, 60, 7, 3.5, S300)], 430, 396, rot=4)]
    b += [briefcase(96, 420, .7, rot=-10), spark(540, 250, 16, W), spark(120, 78, 12, A400), plus(70, 300, 9, INK), ring(560, 330, 12, INK, 5), circ(600, 180, 7, T500)]
    svg('hero-for-lawyers', 640, 560, b, defs=d1 + d2)


def hero_contact():
    d1, f1 = grad(T300, T500)
    d2, f2 = grad(V400, V600)
    scr = [rr(-68, -140, 136, 62, 22, T600), rr(-68, -100, 136, 22, 0, T600), circ(-40, -104, 15, W), rr(-16, -112, 64, 8, 4, W), rr(-16, -98, 40, 6, 3, W, opacity=.7),
           bubble(-56, -60, 96, 44, S100, 'l', 2, S300, 1), bubble(-40, 4, 98, 44, T500, 'r', 2), bubble(-56, 68, 80, 32, S100, 'l', 1, S300, 1),
           rr(-56, 112, 112, 20, 10, S100), circ(46, 122, 7, C500)]
    b = [blob(330, 286, 400, 400, 8, f2, 56, opacity=.9), blob(318, 292, 400, 400, -5, f1, 56), dots(44, 70, 6, 5), dots(500, 440, 6, 4)]
    b += [phone(322, 290, 1.16, rot=-4, screen=''.join(scr))]
    b += [g([rr(4, 8, 172, 60, 20, INK, opacity=.10), rr(0, 0, 172, 60, 20, W), circ(32, 30, 18, GREEN), bubble(22, 21, 20, 14, W, 'l', 0), rr(60, 19, 86, 9, 4.5, INK), rr(60, 36, 56, 7, 3.5, S300)], 36, 180, rot=-6)]
    b += [g([circ(0, 0, 54, A300), headset(0, -14, .56)], 520, 180)]
    b += [g([circ(0, 0, 40, C500), path('M-12,-18 c-6,2 -8,8 -6,14 c4,12 12,20 24,24 c6,2 12,0 14,-6 l-8,-8 l-8,4 c-4,-2 -8,-6 -10,-10 l4,-8 Z', W)], 124, 420)]
    b += [envelope(520, 410, .9, rot=8), spark(580, 300, 14, A300), spark(180, 100, 12, W), plus(60, 330, 9, INK), ring(440, 70, 12, INK, 5)]
    svg('hero-contact', 640, 560, b, defs=d1 + d2)


def step_scene(name, body, c1, c2):
    d, p = panel(480, 300, c1, c2, 30)
    svg(name, 480, 300, [p] + body, defs=d)


def steps():
    step_scene('step-search', [dots(26, 26, 5, 4, fill=W, op=.35), circ(420, 280, 90, W, opacity=.12),
                               profile_card(52, 92, 230, .9, rot=-6, avatar=advocate(47, 52, .34, SKIN[0], 'long'), buttons=False, tone=C400),
                               profile_card(198, 150, 230, .9, rot=4, avatar=advocate(47, 52, .34, SKIN[2], 'short'), buttons=False),
                               magnifier(356, 104, .78), spark(60, 250, 12, A300), spark(440, 40, 10, W)], T400, T600)
    scr = [bubble(-54, -110, 100, 46, S100, 'l', 2, S300, 1), bubble(-44, -44, 100, 46, T500, 'r', 2), bubble(-54, 22, 84, 34, S100, 'l', 1, S300, 1), rr(-54, 92, 108, 30, 15, GREEN), rr(-24, 104, 48, 6, 3, W)]
    step_scene('step-call', [dots(370, 30, 5, 4, fill=W, op=.4), circ(60, 290, 100, W, opacity=.14), phone(240, 190, .98, rot=6, screen=''.join(scr)),
                             g([circ(0, 0, 44, A300), headset(0, -12, .46)], 110, 110), g([circ(0, 0, 34, W), path('M-10,-15 c-5,2 -7,7 -5,12 c3,10 10,17 20,20 c5,2 10,0 12,-5 l-7,-7 l-7,3 c-3,-2 -7,-5 -8,-8 l3,-7 Z', C500)], 384, 196),
                             spark(404, 96, 12, A300), spark(70, 216, 10, W)], C300, C500)
    step_scene('step-connect', [dots(26, 200, 5, 4, fill=INK, op=.16), circ(430, 20, 90, W, opacity=.2),
                                line('M150,150 C200,90 280,90 330,150', INK, 5, stroke_dasharray='2 13'),
                                g([circ(0, 0, 62, W), '<clipPath id="pc1"><circle r="56"/></clipPath><g clip-path="url(#pc1)">%s%s</g>' % (circ(0, 0, 56, T100), person(0, 4, .6, SKIN[0], T500, 'bun'))], 126, 170),
                                g([circ(0, 0, 62, W), '<clipPath id="pc2"><circle r="56"/></clipPath><g clip-path="url(#pc2)">%s%s</g>' % (circ(0, 0, 56, V100), advocate(0, 4, .6, SKIN[2], 'short'))], 354, 170),
                                g([circ(0, 0, 34, INK), lock(0, -6, .44)], 240, 96), badge(396, 124, 17, T500),
                                g([rr(0, 0, 150, 40, 20, INK), circ(22, 20, 9, T400), rr(40, 16, 86, 8, 4, W)], 165, 232), spark(52, 60, 12, W), spark(440, 250, 12, INK)], A200, A400)


def cta_art():
    svg('cta-help', 300, 300, [bubble(40, 70, 190, 96, W, 'l', 3, T600, .35), bubble(124, 178, 150, 70, INK, 'r', 2, W, .8),
                               g([circ(0, 0, 30, A300), headset(0, -8, .32)], 236, 72), spark(40, 220, 12, W), spark(270, 150, 9, A300)])
    svg('cta-advocate', 300, 300, [g(['<clipPath id="ca"><circle r="104"/></clipPath>', circ(0, 0, 104, W, opacity=.22),
                                                                          '<g clip-path="url(#ca)">%s</g>' % advocate(0, 22, 1.12, SKIN[1], 'bun')], 164, 176), g([circ(0, 0, 30, W), badge(0, 0, 22, T500)], 250, 96),
                                   briefcase(62, 244, .5, rot=-10), spark(48, 96, 12, W), spark(274, 244, 9, INK)])


def city_strip():
    b = [rr(0, 232, 1200, 8, 4, S200)]
    tones = [(T600, T500, T400, C400, V500, INK2), (INK2, C400, T500, A400, T600, V500), (V500, T400, INK2, T600, C400, T500)]
    for i, xx in enumerate([6, 404, 802]):
        b.append(skyline(xx, 232, tones[i], .78))
    for xx in [394, 792, 1190]:
        b.append(tree(xx, 232, .7))
    b += [pin(110, 44 + 30, .5), pin(590, 60, .5, T600), pin(1010, 78, .5, V500), circ(300, 40, 16, A300), rr(700, 30, 70, 14, 7, S200), rr(724, 20, 60, 14, 7, S200)]
    svg('city-skyline', 1200, 240, b)


def find_banner():
    d1, f1 = grad(T300, T500)
    b = [blob(290, 150, 380, 210, -4, f1, 40), dots(20, 20, 5, 4), g(skyline(0, 0, (W, T100, W, T100, W, T100), .62), 130, 232, opacity=.5),
         profile_card(40, 54, 220, .8, rot=-5, avatar=advocate(47, 52, .34, SKIN[0], 'bun'), buttons=False, tone=C400),
         profile_card(250, 88, 220, .8, rot=4, avatar=advocate(47, 52, .34, SKIN[2], 'short'), buttons=False),
         pin(300, 64, .62), magnifier(470, 70, .56), spark(508, 200, 12, A300), spark(60, 230, 10, INK)]
    svg('find-lawyer', 560, 280, b, defs=d1)


def empty_state():
    b = [circ(170, 130, 104, T50), dots(24, 30, 4, 4), document(126, 132, 120, 150, rot=-8, seal=False), document(206, 140, 120, 150, rot=7, seal=False, accent=T500),
         magnifier(196, 120, .74), path('M178,104 l36,32 M214,104 l-36,32', 'none', stroke=C500, stroke_width=9, stroke_linecap='round'), spark(292, 60, 12, A300), spark(52, 200, 10, C400), ring(290, 200, 9, INK, 4)]
    svg('empty-search', 340, 260, b)


def auth_art():
    d1, f1 = grad(T300, T500)
    b = [blob(170, 220, 240, 300, -6, f1, 44), dots(20, 20, 5, 4), g([rr(6, 10, 220, 250, 26, INK, opacity=.1), rr(0, 0, 220, 250, 26, W), circ(110, 70, 38, T100), lock(110, 66, .62),
                                                                     rr(28, 132, 164, 30, 12, S100), circ(48, 147, 5, INK), circ(66, 147, 5, INK), circ(84, 147, 5, INK), circ(102, 147, 5, INK), circ(120, 147, 5, INK),
                                                                     rr(28, 176, 164, 34, 17, T600), rr(84, 190, 52, 6, 3, W)], 60, 96, rot=-4), shield(268, 108, .62, C500), g([circ(0, 0, 26, INK), badge(0, 0, 18, T400, INK)], 62, 350),
         spark(300, 300, 12, A300), spark(40, 150, 10, C400)]
    svg('auth-login', 340, 420, b, defs=d1)
    d2, f2 = grad(C300, C500)
    b = [blob(170, 220, 240, 300, 6, f2, 44), dots(240, 20, 5, 4), document(150, 210, 190, 250, rot=5, lines=6), g([circ(0, 0, 40, W), '<clipPath id="aj"><circle r="35"/></clipPath><g clip-path="url(#aj)">%s%s</g>' % (circ(0, 0, 35, V100), advocate(0, 4, .4, SKIN[1], 'long'))], 76, 100),
         g([rr(0, 0, 148, 46, 18, INK), badge(26, 23, 13, T400, INK), rr(48, 14, 76, 8, 4, W), rr(48, 28, 50, 6, 3, S400)], 150, 340, rot=-4), briefcase(280, 110, .46, rot=10), spark(50, 320, 12, A300), spark(300, 240, 10, W)]
    svg('auth-join', 340, 420, b, defs=d2)


def error_art():
    b = [circ(210, 150, 124, C50), dots(30, 30, 5, 4), g(scales(0, 0, .74, tilt=14, pan=A300), 210, 152), document(96, 214, 90, 112, rot=-14, seal=False, lines=3),
         magnifier(330, 84, .5, rot=14), path('M316,70 q14,-16 28,0', 'none', stroke=INK, stroke_width=0), spark(64, 84, 12, A300), spark(366, 220, 12, C400), ring(350, 160, 9, INK, 4), plus(56, 150, 7, INK)]
    svg('error-scales', 420, 300, b)


def about_trust():
    b = [circ(150, 150, 130, W, opacity=.06), g(scales(0, 0, .82, tilt=-6, body=W, pan=A300, accent=C500), 150, 138), spark(40, 60, 12, A300), spark(262, 230, 10, C400), dots(214, 26, 4, 4, fill=W, op=.3)]
    svg('scales-light', 300, 300, b)


def cover(name, c1, c2, body, angle=30):
    d, p = panel(800, 450, c1, c2, angle)
    extra = [circ(700, 430, 190, W, opacity=.10), circ(60, 10, 120, W, opacity=.08), dots(40, 320, 7, 5, fill=W, op=.32), dots(640, 40, 7, 4, fill=W, op=.32)]
    svg(name, 800, 450, [p] + extra + body, defs=d)


def covers():
    cover('blog-default', T400, T700, [g(scales(0, 0, 1.0, tilt=-7, body=INK, pan=A300), 400, 196), book(196, 318, 150, 26, C500), book(210, 292, 124, 26, W, band=T500), book(470, 320, 140, 24, V500),
                                       spark(180, 110, 16, W), spark(630, 250, 14, A300)])
    cover('blog-know-your-rights', V400, V600, [open_book(400, 290, 1.25), shield(400, 150, 1.0, T400), g(rr(0, 0, 120, 40, 20, W) + rr(20, 16, 80, 8, 4, V600), 560, 120, rot=8),
                                                g(rr(0, 0, 110, 40, 20, INK) + rr(20, 16, 70, 8, 4, W), 130, 150, rot=-8), spark(160, 90, 16, A300), spark(660, 300, 14, W)])
    cover('blog-family-divorce', '#fda4af', '#e11d48', [house(400, 300, 1.5, roof=INK, door=C500), heart(400, 96, 1.1, W), g([person(0, 0, .62, SKIN[0], T500, 'long')], 196, 290), g([person(0, 0, .62, SKIN[2], A400, 'short')], 606, 290),
                                                        g([person(0, 0, .4, SKIN[1], V500, 'short')], 280, 322), spark(130, 110, 16, A300), spark(680, 150, 14, W)], 40)
    cover('blog-criminal-law', '#334155', INK, [g([block(60, 120), gavel(0, 0, 1.25, rot=-38, head=W, band=A300, handle=C500)], 300, 200), shield(560, 220, 1.4, C500), courthouse(690, 420, .5, cols=4, wall=S200, col=W, roof=INK2),
                                                spark(150, 100, 16, A300), spark(660, 90, 14, T400)])
    cover('blog-property-real-estate', A300, '#f59e0b', [rr(0, 388, 800, 62, 0, INK, opacity=.12), house(300, 350, 1.7, roof=C500), tree(130, 388, 1.3, T600, T700), tree(470, 388, 1.0, T500, T700),
                                                         document(590, 250, 150, 190, rot=8), pin(660, 150, .8, INK, A300),
                                                         spark(120, 110, 16, W), spark(720, 330, 14, W)])
    cover('blog-consumer-rights', '#7dd3fc', '#0284c7', [bag(300, 240, 1.6, C500, rot=-6), g([rr(6, 10, 150, 200, 14, INK, opacity=.1), rr(0, 0, 150, 200, 14, W)] + [rr(18, 24 + i * 22, 114 * (1 if i % 2 == 0 else .7), 8, 4, S200) for i in range(5)] + [rr(18, 150, 60, 12, 6, INK), rr(96, 150, 36, 12, 6, C500)], 470, 130, rot=7),
                                                         g([circ(0, 0, 44, W), badge(0, 0, 34, T500)], 610, 130), spark(140, 110, 16, A300), spark(690, 320, 14, W)])
    cover('blog-banking-cheques', '#bef264', '#4d7c0f', [courthouse(250, 370, .9, cols=4, roof=INK), cheque(410, 150, 260, 126, rot=-7), coin(560, 330, 40), coin(630, 350, 30), coin(500, 362, 26),
                                                         spark(120, 100, 16, W), spark(700, 110, 14, A300)])
    cover('blog-firm-news', C300, C600, [newspaper(250, 96, 220, 262, rot=-6), megaphone(560, 220, 1.3, rot=-14).replace(C500, W), g(rr(0, 0, 130, 44, 22, INK) + circ(24, 22, 8, T400) + rr(42, 18, 68, 8, 4, W), 520, 340, rot=5),
                                         spark(140, 90, 16, A300), spark(700, 110, 14, W)])


if __name__ == '__main__':
    hero_about(); hero_lawyers(); hero_contact(); steps(); cta_art(); city_strip(); find_banner(); empty_state(); auth_art(); error_art(); about_trust(); covers()
    total = 0
    for f in sorted(os.listdir(OUT)):
        sz = os.path.getsize(os.path.join(OUT, f)); total += sz
        print('%-34s %5.1f KB' % (f, sz / 1024))
    print('total %.1f KB' % (total / 1024))
