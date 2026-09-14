from PIL import Image, ImageDraw
import os

src = r'E:\ziliaoku\miniprogram\generated-1789400305905.png'
im = Image.open(src).convert('RGBA')
w, h = im.size

# Crop centered square that contains the circular avatar, exclude watermark
# Circle roughly spans ~120-900 in x/y on 1024 canvas
left, top = 140, 100
right, bottom = 884, 900
im2 = im.crop((left, top, right, bottom))

# Force circular mask with soft edge so it works as avatar regardless of bg
size = min(im2.size)
im2 = im2.resize((size, size), Image.LANCZOS)

# Optional: apply circular alpha mask
mask = Image.new('L', (size * 4, size * 4), 0)
draw = ImageDraw.Draw(mask)
draw.ellipse((0, 0, size * 4 - 1, size * 4 - 1), fill=255)
mask = mask.resize((size, size), Image.LANCZOS)

out = Image.new('RGBA', (size, size), (0, 0, 0, 0))
out.paste(im2, (0, 0))
out.putalpha(mask)

dst = r'E:\ziliaoku\miniprogram\static\default-avatar.png'
out.save(dst, 'PNG', optimize=True)
print('saved', dst, out.size, os.path.getsize(dst))
