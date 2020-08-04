# zImageOptimizer extensions

Prepared extensions for [zImageOptimizer](https://github.com/zevilz/zImageOptimizer)

## create_webp

Adds converting images to webp. Original images are preserved. A suffix is added to the converted images. Optimization of original images is not disabled. Extension have support for jpg, png, gif, tiff formats (tiff not supported by main script).

WebP quality is set to `80` by default. You can change it in `vars` file. It is not recommended to set a high quality, because the output images will be larger than the input images, and the visual quality will hardly differ from the lower quality. 80-85 is optimal quality.

## png2jpg

Converting PNG images to JPG using ImageMagick tools. Supports checking tools, automatic install dependencies (Lunux, MacOS), optimizing output images.

Available vars (in `vars` file):

- `PNG2JPG_SAVE_FILENAME` - output filename format: `1` (default) - add prefix to original filename (`image.png` -> `image.png.jpg`), `0` - replace old extension (`image.png` -> `image.jpg`, images will be skipped if image with same name exists);
- `PNG2JPG_SAVE_ORIGINAL` - saving original file: `1` (default) - save original file (not saved if `PNG2JPG_SAVE_FILENAME=0`), `0` - remove original file after converting;
- `PNG2JPG_OPTIMIZE_JPG` - optimizing output JPG image: `1` (default) - optimize (supports restore not optimized image if optimized image is bigger), `0` - do not optimize.

## gif2jpg

Converting GIF images to JPG using ImageMagick tools. Supports checking tools, automatic install dependencies (Lunux, MacOS), optimizing output images.

Available vars (in `vars` file):

- `GIF2JPG_SAVE_FILENAME` - output filename format: `1` (default) - add prefix to original filename (`image.gif` -> `image.gif.jpg`), `0` - replace old extension (`image.gif` -> `image.jpg`, images will be skipped if image with same name exists);
- `GIF2JPG_SAVE_ORIGINAL` - saving original file: `1` (default) - save original file (not saved if `GIF2JPG_SAVE_FILENAME=0`), `0` - remove original file after converting;
- `GIF2JPG_OPTIMIZE_JPG` - optimizing output JPG image: `1` (default) - optimize (supports restore not optimized image if optimized image is bigger), `0` - do not optimize.

## tiff2jpg

Converting TIFF images to JPG using ImageMagick tools. Supports checking tools, automatic install dependencies (Lunux, MacOS), optimizing output images.

Available vars (in `vars` file):

- `TIFF2JPG_SAVE_FILENAME` - output filename format: `1` (default) - add prefix to original filename (`image.tiff` -> `image.tiff.jpg`), `0` - replace old extension (`image.tiff` -> `image.jpg`, images will be skipped if image with same name exists);
- `TIFF2JPG_SAVE_ORIGINAL` - saving original file: `1` (default) - save original file (not saved if `TIFF2JPG_SAVE_FILENAME=0`), `0` - remove original file after converting;
- `TIFF2JPG_OPTIMIZE_JPG` - optimizing output JPG image: `1` (default) - optimize (supports restore not optimized image if optimized image is bigger), `0` - do not optimize.

## bmp2jpg

Converting BMP images to JPG using ImageMagick tools. Supports checking tools, automatic install dependencies (Lunux, MacOS), optimizing output images.

Available vars (in `vars` file):

- `BMP2JPG_SAVE_FILENAME` - output filename format: `1` (default) - add prefix to original filename (`image.bmp` -> `image.bmp.jpg`), `0` - replace old extension (`image.bmp` -> `image.jpg`, images will be skipped if image with same name exists);
- `BMP2JPG_SAVE_ORIGINAL` - saving original file: `1` (default) - save original file (not saved if `BMP2JPG_SAVE_FILENAME=0`), `0` - remove original file after converting;
- `BMP2JPG_OPTIMIZE_JPG` - optimizing output JPG image: `1` (default) - optimize (supports restore not optimized image if optimized image is bigger), `0` - do not optimize.

## disable_jpg

Completely remove JPG support including checking tools and find images.

## disable_png

Completely remove PNG support including checking tools and find images.

## disable_gif

Completely remove GIF support including checking tools and find images.

## disable_optimize

Disable build-in optimize. This helpful for use your own extensions which use third-party ways to optimize or something else operations with images (like converting).
