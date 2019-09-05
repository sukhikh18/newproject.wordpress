<?php

if ( ! class_exists( 'WP_Bootstrap_Navwalker' ) ) {
    /**
     * WP_Bootstrap_Navwalker class.
     *
     * @extends Walker_Nav_Menu
     */
    class WP_Bootstrap_Navwalker extends Walker_Nav_Menu {

        /**
         * Starts the list before the elements are added.
         *
         * @since WP 3.0.0
         *
         * @see Walker_Nav_Menu::start_lvl()
         *
         * @param string   $output Used to append additional content (passed by reference).
         * @param int      $depth  Depth of menu item. Used for padding.
         * @param stdClass $args   An object of wp_nav_menu() arguments.
         */
        public function start_lvl( &$output, $depth = 0, $args = array() ) {
            if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
                $t = '';
                $n = '';
            } else {
                $t = "\t";
                $n = "\n";
            }
            $indent = str_repeat( $t, $depth );
            // Default class to add to the file.
            $classes = array( 'dropdown-menu' );
            /**
             * Filters the CSS class(es) applied to a menu list element.
             *
             * @since WP 4.8.0
             *
             * @param array    $classes The CSS classes that are applied to the menu `<ul>` element.
             * @param stdClass $args    An object of `wp_nav_menu()` arguments.
             * @param int      $depth   Depth of menu item. Used for padding.
             */
            $class_names = join( ' ', apply_filters( 'nav_menu_submenu_css_class', $classes, $args, $depth ) );
            $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';
            /**
             * The `.dropdown-menu` container needs to have a labelledby
             * attribute which points to it's trigger link.
             *
             * Form a string for the labelledby attribute from the the latest
             * link with an id that was added to the $output.
             */
            $labelledby = '';
            // find all links with an id in the output.
            preg_match_all( '/(<a.*?id=\"|\')(.*?)\"|\'.*?>/im', $output, $matches );
            // with pointer at end of array check if we got an ID match.
            if ( end( $matches[2] ) ) {
                // build a string to use as aria-labelledby.
                $labelledby = 'aria-labelledby="' . end( $matches[2] ) . '"';
            }
            $output .= "{$n}{$indent}<ul$class_names $labelledby role=\"menu\">{$n}";
        }

        /**
         * Starts the element output.
         *
         * @since WP 3.0.0
         * @since WP 4.4.0 The {@see 'nav_menu_item_args'} filter was added.
         *
         * @see Walker_Nav_Menu::start_el()
         *
         * @param string   $output Used to append additional content (passed by reference).
         * @param WP_Post  $item   Menu item data object.
         * @param int      $depth  Depth of menu item. Used for padding.
         * @param stdClass $args   An object of wp_nav_menu() arguments.
         * @param int      $id     Current item ID.
         */
        public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
            if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
                $t = '';
                $n = '';
            } else {
                $t = "\t";
                $n = "\n";
            }
            $indent = ( $depth ) ? str_repeat( $t, $depth ) : '';

            $classes = empty( $item->classes ) ? array() : (array) $item->classes;

            // Initialize some holder variables to store specially handled item icons.
            $icon_classes    = array();

            /**
             * Get an updated $classes array without icon classes.
             */
            $classes = self::separate_icons_from_classes( $classes, $icon_classes, $depth );

            // Join any icon classes plucked from $classes into a string.
            $icon_class_string = join( ' ', $icon_classes );

            /**
             * Filters the arguments for a single nav menu item.
             *
             *  WP 4.4.0
             *
             * @param stdClass $args  An object of wp_nav_menu() arguments.
             * @param WP_Post  $item  Menu item data object.
             * @param int      $depth Depth of menu item. Used for padding.
             */
            $args = apply_filters( 'nav_menu_item_args', $args, $item, $depth );

            // Add .dropdown or .active classes where they are needed.
            if ( isset( $args->has_children ) && $args->has_children ) {
                $classes[] = 'dropdown';
            }
            if ( in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-parent', $classes, true ) ) {
                $classes[] = 'active';
            }

            // Add some additional default classes to the item.
            $classes[] = 'menu-item-' . $item->ID;
            $classes[] = 'nav-item';

            // Allow filtering the classes.
            $classes = apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth );

            // Form a string of classes in format: class="class_names".
            $class_names = join( ' ', $classes );
            $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

            /**
             * Filters the ID applied to a menu item's list item element.
             *
             * @since WP 3.0.1
             * @since WP 4.1.0 The `$depth` parameter was added.
             *
             * @param string   $menu_id The ID that is applied to the menu item's `<li>` element.
             * @param WP_Post  $item    The current menu item.
             * @param stdClass $args    An object of wp_nav_menu() arguments.
             * @param int      $depth   Depth of menu item. Used for padding.
             */
            $id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );
            $id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

            $schema = '';
            if( get_theme_mod('allow_schema_org') ) {
                $schema .= ' itemscope="itemscope"';
                $schema .= ' itemtype="https://www.schema.org/SiteNavigationElement"';
            }

            $output .= $indent . '<li' . $schema . $id . $class_names . '>';

            // initialize array for holding the $atts for the link item.
            $atts = array();

            // Set title from item to the $atts array - if title is empty then
            // default to item title.
            if ( empty( $item->attr_title ) ) {
                $atts['title'] = ! empty( $item->title ) ? strip_tags( $item->title ) : '';
            } else {
                $atts['title'] = $item->attr_title;
            }

            $atts['target'] = ! empty( $item->target ) ? $item->target : '';
            $atts['rel']    = ! empty( $item->xfn ) ? $item->xfn : '';
            // If item has_children add atts to <a>.

            if ( isset( $args->has_children ) && $args->has_children && 0 === $depth ) { // && $args->depth > 1
                $atts['href']          = '#';
                $atts['data-toggle']   = 'dropdown';
                $atts['aria-haspopup'] = 'true';
                $atts['aria-expanded'] = 'false';
                $atts['class']         = 'dropdown-toggle nav-link';
                $atts['id']            = 'menu-item-dropdown-' . $item->ID;
            } else {
                $atts['href'] = ! empty( $item->url ) ? $item->url : '#';
                // Items in dropdowns use .dropdown-item instead of .nav-link.
                if ( $depth > 0 ) {
                    $atts['class'] = 'dropdown-item';
                } else {
                    $atts['class'] = 'nav-link';
                }
            }

            // Allow filtering of the $atts array before using it.
            $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

            // Build a string of html containing all the atts for the item.
            $attributes = '';
            foreach ( $atts as $attr => $value ) {
                if ( ! empty( $value ) ) {
                    $value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                    $attributes .= ' ' . $attr . '="' . $value . '"';
                }
            }

            /**
             * START appending the internal item contents to the output.
             */
            $item_output = isset( $args->before ) ? $args->before : '';

            // With no link mod type set this must be a standard <a> tag.
            $item_output .= '<a' . $attributes . '>';

            /**
             * Initiate empty icon var, then if we have a string containing any
             * icon classes form the icon markup with an <i> element. This is
             * output inside of the item before the $title (the link text).
             */
            $icon_html = '';
            if ( ! empty( $icon_class_string ) ) {
                // append an <i> with the icon classes to what is output before links.
                $icon_html = '<i class="' . esc_attr( $icon_class_string ) . '" aria-hidden="true"></i> ';
            }

            /** This filter is documented in wp-includes/post-template.php */
            $title = apply_filters( 'the_title', $item->title, $item->ID );

            /**
             * Filters a menu item's title.
             *
             * @since WP 4.4.0
             *
             * @param string   $title The menu item's title.
             * @param WP_Post  $item  The current menu item.
             * @param stdClass $args  An object of wp_nav_menu() arguments.
             * @param int      $depth Depth of menu item. Used for padding.
             */
            $title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

            // Put the item contents into $output.
            $item_output .= isset( $args->link_before ) ? $args->link_before . $icon_html . $title . $args->link_after : '';
            $item_output .= '</a>';

            $item_output .= isset( $args->after ) ? $args->after : '';

            /**
             * END appending the internal item contents to the output.
             */
            $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
        }

        /**
         * Traverse elements to create list from elements.
         *
         * Display one element if the element doesn't have any children otherwise,
         * display the element and its children. Will only traverse up to the max
         * depth and no ignore elements under that depth. It is possible to set the
         * max depth to include all depths, see walk() method.
         *
         * This method should not be called directly, use the walk() method instead.
         *
         * @since WP 2.5.0
         *
         * @see Walker::start_lvl()
         *
         * @param object $element           Data object.
         * @param array  $children_elements List of elements to continue traversing (passed by reference).
         * @param int    $max_depth         Max depth to traverse.
         * @param int    $depth             Depth of current element.
         * @param array  $args              An array of arguments.
         * @param string $output            Used to append additional content (passed by reference).
         */
        public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
            if ( ! $element ) return;

            $id_field = $this->db_fields['id'];
            // Display this element.
            if ( is_object( $args[0] ) ) {
                $args[0]->has_children = ! empty( $children_elements[ $element->$id_field ] );
            }

            parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
        }

        /**
         * Menu Fallback
         * =============
         * If this function is assigned to the wp_nav_menu's fallback_cb variable
         * and a menu has not been assigned to the theme location in the WordPress
         * menu manager the function with display nothing to a non-logged in user,
         * and will add a link to the WordPress menu manager if logged in as an admin.
         *
         * @param array $args passed from the wp_nav_menu function.
         */
        public static function fallback( $args ) {
            if ( current_user_can( 'edit_theme_options' ) ) {

                /* Get Arguments. */
                $container       = $args['container'];
                $container_id    = $args['container_id'];
                $container_class = $args['container_class'];
                $menu_class      = $args['menu_class'];
                $menu_id         = $args['menu_id'];

                // initialize var to store fallback html.
                $fallback_output = '';

                if ( $container ) {
                    $fallback_output .= '<' . esc_attr( $container );
                    if ( $container_id ) {
                        $fallback_output .= ' id="' . esc_attr( $container_id ) . '"';
                    }
                    if ( $container_class ) {
                        $fallback_output .= ' class="' . esc_attr( $container_class ) . '"';
                    }
                    $fallback_output .= '>';
                }
                $fallback_output .= '<ul';
                if ( $menu_id ) {
                    $fallback_output .= ' id="' . esc_attr( $menu_id ) . '"'; }
                if ( $menu_class ) {
                    $fallback_output .= ' class="' . esc_attr( $menu_class ) . '"'; }
                $fallback_output .= '>';
                $fallback_output .= '<li><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '" title="' . esc_attr__( 'Add a menu', 'wp-bootstrap-navwalker' ) . '">' . esc_html__( 'Add a menu', 'wp-bootstrap-navwalker' ) . '</a></li>';
                $fallback_output .= '</ul>';
                if ( $container ) {
                    $fallback_output .= '</' . esc_attr( $container ) . '>';
                }

                // if $args has 'echo' key and it's true echo, otherwise return.
                if ( array_key_exists( 'echo', $args ) && $args['echo'] ) {
                    echo $fallback_output; // WPCS: XSS OK.
                } else {
                    return $fallback_output;
                }
            }
        }

        /**
         * Find any custom icon classes and store in their holder
         * arrays then remove them from the main classes array.
         *
         * Supported iconsets: Font Awesome 4/5, Glypicons
         *
         * NOTE: This accepts the icon arrays by reference.
         *
         * @since 4.0.0
         *
         * @param array   $classes         an array of classes currently assigned to the item.
         * @param array   $icon_classes    an array to hold icon classes.
         * @param integer $depth           an integer holding current depth level.
         *
         * @return array  $classes         a maybe modified array of classnames.
         */
        private function separate_icons_from_classes( $classes, &$icon_classes, $depth ) {
            // Loop through $classes array to find icon classes.
            foreach ( $classes as $key => $class ) {
                // If any special classes are found, store the class in it's
                // holder array and and unset the item from $classes.
                if ( preg_match( '/^fa-(\S*)?|^fa(s|r|l|b)?(\s?)?$/i', $class ) ) {
                    // Font Awesome.
                    $icon_classes[] = $class;
                    unset( $classes[ $key ] );
                } elseif ( preg_match( '/^glyphicon-(\S*)?|^glyphicon(\s?)$/i', $class ) ) {
                    // Glyphicons.
                    $icon_classes[] = $class;
                    unset( $classes[ $key ] );
                }
            }

            return $classes;
        }

        /**
         * Wraps the passed text in a screen reader only class.
         *
         * @since 4.0.0
         *
         * @param string $text the string of text to be wrapped in a screen reader class.
         * @return string      the string wrapped in a span with the class.
         */
        private function wrap_for_screen_reader( $text = '' ) {
            if ( $text ) {
                $text = '<span class="sr-only">' . $text . '</span>';
            }
            return $text;
        }
    }
}