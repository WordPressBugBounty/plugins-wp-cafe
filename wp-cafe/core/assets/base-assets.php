<?php
namespace WpCafe\Assets;

use WpCafe\Contracts\Hookable_Service_Contract;

/**
 * Main assets management class
 */
abstract class Base_Assets implements Hookable_Service_Contract {
    /**
     * Get all scripts
     *
     * @return  array list of script to register
     */
    abstract public function get_scripts();

    /**
     * Get all styles
     *
     * @return  array   list of styles to regiser
     */
    abstract public function get_styles();

    /**
     * Register scripts and styles
     *
     * @return  void
     */
    public function register_styles_scripts() {
        $this->register_global_scripts();
        $this->register_scripts();
        $this->register_styles();
    }

    /**
     * Register scripts
     *
     * @param  array $scripts
     *
     * @return void
     */
    public function register_scripts() {
        $scripts = $this->get_scripts();

        foreach ( $scripts as $handle => $script ) {
            $deps      = isset( $script['deps'] ) ? $script['deps'] : [];
            $in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : true;
            $version   = isset( $script['version'] ) ? $script['version'] : $this->get_version( $script['src'] );

            $deps = $this->get_dependencies( $script['src'], $deps );

            if ( in_array( 'wp-i18n', $deps ) ) {
                $deps[] = 'wpcafe-i18n';
            }

            wp_register_script( $handle, $script['src'], $deps, $version, $in_footer );
        }
    }

    /**
     * Register global scripts
     *
     * @return  void
     */
    private function register_global_scripts() {
        $scripts = [
            'wpcafe-i18n' => [
                'src' => wpcafe()->assets_url . '/build/js/i18n-loader.js',
            ],
        ];

        foreach ( $scripts as $handle => $script ) {
            $deps      = isset( $script['deps'] ) ? $script['deps'] : [];
            $in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : true;
            $version   = isset( $script['version'] ) ? $script['version'] : $this->get_version( $script['src'] );

            $deps = $this->get_dependencies( $script['src'], $deps );

            wp_register_script( $handle, $script['src'], $deps, $version, $in_footer );
        }

    }

    /**
     * Register styles
     *
     * @param  array $styles
     *
     * @return void
     */
    public function register_styles() {
        $styles = $this->get_styles();

        foreach ( $styles as $handle => $style ) {
            $deps    = isset( $style['deps'] ) ? $style['deps'] : false;
            $version = wpcafe()->version;

            wp_register_style( $handle, $style['src'], $deps, $version );
        }
    }

    /**
     * Get script and style file dependencies
     *
     * @param   string  $file_name
     * @param   array  $deps
     *
     * @return  array
     */
    private function get_dependencies( $file_name, $deps = [] ) {
        $assets = $this->get_file_assets( $file_name );

        $assets_deps = ! empty( $assets['dependencies'] ) ? $assets['dependencies'] : [];

        $merged_deps = array_merge( $assets_deps, $deps );

        return $merged_deps;
    }

    /**
     * Get file assets
     *
     * @param   string  $file_name
     *
     * @return  array
     */
    private function get_file_assets( $file_url ) {
        $file   = $this->get_file_path( $file_url );
        $assets = [];

        if ( file_exists( $file ) ) {
            $assets = include $file;
        }

        return $assets;
    }

    /**
     * Get script file version
     *
     * @param   string  $file_name
     *
     * @return  string
     */
    private function get_version( $file_name ) {
        $assets      = $this->get_file_assets( $file_name );
        $assets_vers = ! empty( $assets['version'] ) ? $assets['version'] : wpcafe()->version;
        return $assets_vers;
    }

    /**
     * Get file path from url
     *
     * @param   string  $url
     *
     * @return string
     */
    private function get_file_path( $url ) {
        // Check if the URL is valid
        if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return false;
        }

        // Parse the URL
        $url_parts = wp_parse_url( $url );

        // Check if the URL has a path component
        if ( ! isset( $url_parts['path'] ) ) {
            return false; // URL does not contain a path
        }

        $clean_path = str_replace( '.js', '.asset.php', $url_parts['path'] );

        // Get the file path from the URL path.
        if ( isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
            $file_path = sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) . $clean_path;
        } else {
            $file_path = ''; // Or another appropriate fallback.
        }

        // Check if the file exists
        if ( ! file_exists( $file_path ) ) {
            return false; // File does not exist
        }

        return $file_path;
    }

     /**
     * Enqueue i18n loader and set its state
     *
     * @return void
     */
    protected function enqueue_i18n_loader() {
        $locale = determine_locale();
        $data = [
            'baseUrl'     => false,
            'locale'      => $locale,
            'domainMap'   => [],
            'domainPaths' => [],
            'translationMap' => $this->get_translation_map( $locale, 'wp-cafe' ),
        ];

        $lang_dir    = WP_LANG_DIR;
        $content_dir = WP_CONTENT_DIR;
        $abspath     = ABSPATH;

        if ( strpos( $lang_dir, $content_dir ) === 0 ) {
            $data['baseUrl'] = content_url( substr( trailingslashit( $lang_dir ), strlen( trailingslashit( $content_dir ) ) ) );
        } elseif ( strpos( $lang_dir, $abspath ) === 0 ) {
            $data['baseUrl'] = site_url( substr( trailingslashit( $lang_dir ), strlen( untrailingslashit( $abspath ) ) ) );
        }

        wp_enqueue_script( 'wpcafe-i18n' );

        $data['domainMap']   = (object) $data['domainMap'];
        $data['domainPaths'] = (object) $data['domainPaths'];

        wp_add_inline_script(
            'wpcafe-i18n',
            'window.wpCafeI18nLoader = window.wpCafeI18nLoader || {}; window.wpCafeI18nLoader.state = ' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES ) . ';',
            'after'
        );
    }

    /**
     * Build a hash-to-url map for available translation JSON files.
     *
     * @param string $locale Translation locale.
     * @param string $domain Text domain.
     *
     * @return array<string, string>
     */
    private function get_translation_map( $locale, $domain ) {
        $directories = $this->get_translation_directories();
        $map         = [];

        $domain_pattern = preg_quote( $domain, '/' );
        $locale_pattern = preg_quote( $locale, '/' );
        $filename_regex = '/^' . $domain_pattern . '-' . $locale_pattern . '-([a-f0-9]{32})\.json$/i';

        foreach ( $directories as $directory ) {
            if ( empty( $directory['dir'] ) || empty( $directory['url'] ) || ! is_dir( $directory['dir'] ) ) {
                continue;
            }

            $files = glob( trailingslashit( $directory['dir'] ) . $domain . '-' . $locale . '-*.json' );

            if ( empty( $files ) ) {
                continue;
            }

            foreach ( $files as $file ) {
                $basename = basename( $file );

                if ( preg_match( $filename_regex, $basename, $matches ) ) {
                    $map[ $matches[1] ] = trailingslashit( $directory['url'] ) . $basename;
                }
            }
        }

        return $map;
    }

    /**
     * Get translation directories from standard and plugin-specific paths.
     *
     * @return array<int, array<string, string>>
     */
    private function get_translation_directories() {
        return [
            [
                'dir' => trailingslashit( WP_LANG_DIR ) . 'plugins',
                'url' => content_url( 'languages/plugins' ),
            ],
            [
                'dir' => trailingslashit( WP_LANG_DIR ) . 'loco/plugins',
                'url' => content_url( 'languages/loco/plugins' ),
            ],
            [
                'dir' => trailingslashit( WP_CONTENT_DIR ) . 'wp-cafe/languages',
                'url' => content_url( 'wp-cafe/languages' ),
            ],
            [
                'dir' => trailingslashit( wpcafe()->text_domain_directory ),
                'url' => trailingslashit( wpcafe()->plugin_url ) . 'languages',
            ],
        ];
    }
}