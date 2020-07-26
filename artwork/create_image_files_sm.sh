#!/bin/sh
magick montage @artwork/tile_list -crop 182x182+35+35 -tile 8x6 -depth 16 -colorspace sRGB -geometry 100x100+0+0 img/tiles_sm.jpg

magick montage @artwork/flood_list -crop 180.6x250.8+35.5+36.8 -tile 5x5 -depth 16 -colorspace sRGB -geometry 100x138.88+0+0 img/flood_sm.jpg

magick montage @artwork/treasure_list -crop 180.6x250.8+35.5+36.8 -tile 8x1 -depth 16 -colorspace sRGB -geometry 100x138.88+0+0 img/treasure_sm.jpg

magick montage @artwork/player_list -crop 180.6x250.8+35.5+36.8 -tile 6x2 -depth 16 -colorspace sRGB -geometry 100x138.88+0+0 img/player_sm.jpg

magick artwork/flood_meter/FloodMeter_FINAL.pdf -crop 162x489+223+141 -depth 16 -colorspace sRGB -geometry 100x301.85+0+0 img/flood_meter_sm.jpg

magick artwork/flood_meter/Slider.pdf -crop 339x144+157.5+313.9 -depth 16 -colorspace sRGB -geometry 91.95x39.06+0+0 img/slider_sm.png

# reference
# https://imagemagick.org/script/command-line-processing.php