from PIL import Image
import os

src = r'E:\ziliaoku\miniprogram\generated-1789399327173.png'
im = Image.open(src).convert('RGBA')

# Crop character + gifts + coins; exclude bottom-right AI watermark
im2 = im.crop((280, 60, 860, 960))
cw, ch = im2.size

# Sample border and flood-fill ONLY very-close mint (preserve cream ribbons)
from collections import deque

srcp = im2.load()
border = []
for x in range(0, cw, 6):
    border.append(srcp[x, 1][:3])
    border.append(srcp[x, ch - 2][:3])
for y in range(0, ch, 6):
    border.append(srcp[1, y][:3])
    border.append(srcp[cw - 2, y][:3])
br = sum(p[0] for p in border) // len(border)
bg = sum(p[1] for p in border) // len(border)
bb = sum(p[2] for p in border) // len(border)

TOL = 22  # tighter so cream ribbons stay


def near_bg(r, g, b):
    return abs(r - br) <= TOL and abs(g - bg) <= TOL and abs(b - bb) <= TOL


alpha = [[255] * cw for _ in range(ch)]
visited = [[False] * cw for _ in range(ch)]
q = deque()
for x in range(cw):
    for y in (0, ch - 1):
        if not visited[y][x] and near_bg(*srcp[x, y][:3]):
            visited[y][x] = True
            alpha[y][x] = 0
            q.append((x, y))
for y in range(ch):
    for x in (0, cw - 1):
        if not visited[y][x] and near_bg(*srcp[x, y][:3]):
            visited[y][x] = True
            alpha[y][x] = 0
            q.append((x, y))

while q:
    x, y = q.popleft()
    for dx, dy in ((1, 0), (-1, 0), (0, 1), (0, -1)):
        nx, ny = x + dx, y + dy
        if 0 <= nx < cw and 0 <= ny < ch and not visited[ny][nx]:
            if near_bg(*srcp[nx, ny][:3]):
                visited[ny][nx] = True
                alpha[ny][nx] = 0
                q.append((nx, ny))

out = Image.new('RGBA', (cw, ch), (0, 0, 0, 0))
op = out.load()
for y in range(ch):
    for x in range(cw):
        r, g, b, a = srcp[x, y]
        if alpha[y][x] == 0:
            op[x, y] = (0, 0, 0, 0)
        else:
            edge = False
            for dx, dy in ((1, 0), (-1, 0), (0, 1), (0, -1)):
                nx, ny = x + dx, y + dy
                if 0 <= nx < cw and 0 <= ny < ch and alpha[ny][nx] == 0:
                    edge = True
                    break
            if edge and abs(r - br) <= 40 and abs(g - bg) <= 40 and abs(b - bb) <= 40:
                op[x, y] = (r, g, b, 140)
            else:
                op[x, y] = (r, g, b, a)

dst = r'E:\ziliaoku\miniprogram\static\user-hero.png'
out.save(dst, 'PNG', optimize=True)
print('saved', dst, out.size, 'bytes', os.path.getsize(dst), 'bg', br, bg, bb)
