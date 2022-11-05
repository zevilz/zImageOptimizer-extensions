# wp_change_ext

Changes attacments data and urls in WordPress database after converting images via extensions from this repository.

## Requrements

- [wp-cli](https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar) installed in main script binary paths (`/bin /usr/bin /usr/local/bin` by default) with name `wp` and marked as executable (`chmod +x wp`);
- [zio-helper.php](https://github.com/zevilz/zImageOptimizer-extensions/blob/master/wp_change_ext/zio-helper.php) must be copied in `wp-content/mu-plugins` directory;
- one or more installed extensions for converting from this repository ([png2jpg](https://github.com/zevilz/zImageOptimizer-extensions#png2jpg), [gif2jpg](https://github.com/zevilz/zImageOptimizer-extensions#gif2jpg), [tiff2jpg](https://github.com/zevilz/zImageOptimizer-extensions#tiff2jpg), [bmp2jpg](https://github.com/zevilz/zImageOptimizer-extensions#bmp2jpg)).

The script can install wp-cli automatically into /bin/wp. You can did it in normal mode or in check mode with included extension:

```bash
bash zImageOptimizer.sh -c -ext wp_change_ext
```

## Usage

Notes:
- all old images will deleted when replacements is success (specified vars from extensions for converting will be ignored);
- old images extensions will not saved in filenames (specified vars from extensions for converting will be ignored);
- make sure that there are no images in the working directory that cannot be converted (used in styles, theme etc.), use [`-e (--exclude)`](https://github.com/zevilz/zImageOptimizer#excluding-foldersfiles-from-search) option to exclude them;
- you must set WordPress directory (`/path/to/wordpress/site/wp-content/uploads`) as a working directory so that the script can determine the site root for wp-cli, or use `WP_ROOT` var if you want set custom working directory;
- not necessary clear all images cache if you use [Kama Thumbnail](https://wordpress.org/plugins/kama-thumbnail/) plugin, the script automatically deletes the cache of the converted and replaced image.

Basic usage using with png2jpg extension:

```bash
bash zImageOptimizer.sh -p /path/to/wordpress/site/wp-content/uploads -ext png2jpg,wp_change_ext
```

Usage using with png2jpg extension excluding specified files and dirs:

```bash
bash zImageOptimizer.sh -p /path/to/wordpress/site/wp-content/uploads -e "site_logo,2021/10/image.png" -ext png2jpg,wp_change_ext
```

Usage using with png2jpg extension with time marker (pereodical cron runs):

```bash
bash zImageOptimizer.sh -p /path/to/wordpress/site/wp-content/uploads -n -m /path/to/marker/directory/markerName -ext png2jpg,wp_change_ext
```

# Variables

- `WP_ROOT` - WordPress root directory, set it if you use custom directory as working directory (not `wp-content/uploads`), empty by default;
- `WPCLI_CUSTOM_PATH` - WP-CLI custom path (ex.: `/home/user/wp-cli.phar`), empty by default;
- `FORCE_RETRY` - force retry replace data in database: `1` (default) - eneble infinite retries for replace data while it will be successfully replaced (recommended, because wp-cli may crash when short-term fatal errors occur on the site or updates), `0` - disable infinite replace retries and restore old data and revert converting (the script will be remember failed replacements if image is not attachment and trying to replace them once after, not recommended because it make conflicts and broken data in some cases);
- `WP_REPLACE_TABLES` - spaces separated list of tables for replace images urls (wildcard is supported), empty by default;
- `WP_REPLACE_ALL_TABLES` - replace images urls in all tables: `1` (default) - search and replace in all tables (`--all-tables` parameter in wp-cli), `0` - use only tables registered in `$wpdb` object;
- `WP_REPLACE_NETWORK` - replace images urls in all network tables: `1` - search and replace in all network tables registered in `$wpdb` object on multisite install (`--network` parameter in wp-cli), `0` (default) - use only tables registered in `$wpdb` object, this var ignored if `WP_REPLACE_ALL_TABLES=1`;
- `WP_REPLACE_SKIP_TABLES` - comma separated list of tables for exclude for search and replace urls (wildcard is supported, `--skip-tables=` parameter in wp-cli), empty by default;
- `WP_REPLACE_SKIP_COLUMNS` - comma separated list of columns names for exclude for search and replace urls (wildcard is supported, `--skip-columns=` parameter in wp-cli), empty by default;

You must copy `vars_template` file as `vars` in same directory for modify variables.

Hint: use `WP_REPLACE_*` vars for filtering tables and columns on which wp-cli will be make replacements (it is usefull on big databases and not optimized code for decrease replace time).
