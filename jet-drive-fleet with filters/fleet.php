<?php
/**
 * Plugin Name: Jet Drive Fleet
 * Description: A custom Fleet post type for the Jet Drive Fleet Page.
 * Version: 2.0
 * Author: Cup O Code
 * Author URL: www.cupocode.com
 * Notes: Updated to show fleet in alphabetical order, remove limit of number of items shown per page, won't display item labels if no data is entered when creating the card
 */

// Create Custom Post Type
function fleet_custom_post_type() {
    $args = array(
        'public' => true,
        'label'  => 'Fleet',
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-welcome-widgets-menus',
    );
    register_post_type('fleet', $args);
}
add_action('init', 'fleet_custom_post_type');

// Add Meta Boxes
function fleet_add_meta_boxes() {
    add_meta_box('fleet_meta_box', 'Fleet Details', 'fleet_display_meta_box', 'fleet', 'normal', 'high');
}
add_action('add_meta_boxes', 'fleet_add_meta_boxes');

// Show Custom Fields in Meta Box
// Modify fleet_display_meta_box function
function fleet_display_meta_box($post) {
    wp_nonce_field(basename(__FILE__), "meta-box-nonce");

    $model = get_post_meta($post->ID, 'model', true);
    $capacity = get_post_meta($post->ID, 'capacity', true);
    $loa = get_post_meta($post->ID, 'loa', true);
    // $available = get_post_meta($post->ID, 'available', true);
    $options = get_option('fleet_options');

    echo 'Model: <input name="model" type="text" value="' . $model . '"><br>';
    echo 'Capacity: <input name="capacity" type="text" value="' . $capacity . '"><br>';
    echo 'LOA: <input name="loa" type="text" value="' . $loa . '"><br>';
    // echo 'Available: <input name="available" type="text" value="' . $available . '"><br><br>';

    $locations = [
        1 => 'Ocean City',
        2 => 'Somers Point',
        3 => 'Avalon',
        4 => 'New Gretna',
        5 => 'Brick',
        6 => 'Brigantine'
    ];
    foreach ($locations as $i => $location) {
        $location_checked = get_post_meta($post->ID, "location$i", true) == 'on' ? ' checked' : '';
        echo '<input type="checkbox" id="location' . $i . '" name="location' . $i . '" ' . $location_checked . '>';
        echo '<label for="location' . $i . '">' . $location . '</label><br>';
    }

    echo '<br>';

    foreach ($locations as $i => $location) {
        $plus_fleet_checked = get_post_meta($post->ID, "plus_fleet_location$i", true) == 'on' ? ' checked' : '';
        echo '<input type="checkbox" id="plus_fleet_location' . $i . '" name="plus_fleet_location' . $i . '" ' . $plus_fleet_checked . '>';
        echo '<label for="plus_fleet_location' . $i . '"><span class="plus_fleet_icon">PLUS+</span> ' . $location . ' Fleet</label><br>';
    }
}

// Save Custom Fields
// Modify save_custom_meta_box function
function save_custom_meta_box($post_id, $post, $update) {
    if (!isset($_POST["meta-box-nonce"]) || !wp_verify_nonce($_POST["meta-box-nonce"], basename(__FILE__)))
        return $post_id;

    if (!current_user_can("edit_post", $post_id))
        return $post_id;

    if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
        return $post_id;

    // $fields = ['model', 'capacity', 'loa', 'available'];
    $fields = ['model', 'capacity', 'loa'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $meta_box_text_value = $_POST[$field];
            update_post_meta($post_id, $field, $meta_box_text_value);
        }
    }

    $locations = [
        1 => 'Ocean City',
        2 => 'Somers Point',
        3 => 'Avalon',
        4 => 'New Gretna',
        5 => 'Brick',
        6 => 'Brigantine'
    ];
    foreach ($locations as $i => $location) {
        $location_field = "location$i";
        $plus_fleet_field = "plus_fleet_location$i";

        $location_value = isset($_POST[$location_field]) ? 'on' : 'off';
        $plus_fleet_value = isset($_POST[$plus_fleet_field]) ? 'on' : 'off';

        update_post_meta($post_id, $location_field, $location_value);
        update_post_meta($post_id, $plus_fleet_field, $plus_fleet_value);
    }
}
add_action("save_post", "save_custom_meta_box", 10, 3);


// Hide Default WP Text Editor
function hide_default_editor() {
    global $post_type;
    if ($post_type == 'fleet') {
        echo '<style>#postdivrich { display: none; }</style>';
    }
}
add_action('admin_head', 'hide_default_editor');


// Create Settings Page
function fleet_add_submenu_page() {
    add_submenu_page(
        'edit.php?post_type=fleet',
        'Fleet Settings',
        'Settings',
        'manage_options',
        'fleet-settings',
        'fleet_display_settings_page'
    );
}
add_action('admin_menu', 'fleet_add_submenu_page');

function fleet_display_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Add error/update messages
    if (isset($_GET['settings-updated'])) {
        add_settings_error('fleet_messages', 'fleet_message', __('Settings Saved', 'fleet'), 'updated');
    }
        // Show error/update messages
        settings_errors('fleet_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
            <?php
            settings_fields('fleet');
            do_settings_sections('fleet');
            submit_button('Save Settings');
            ?>
            </form>
        </div>
        <?php
    }
    
    function fleet_settings_init() {
        register_setting('fleet', 'fleet_options');
    
        add_settings_section(
            'fleet_section',
            __('Locations', 'fleet'),
            'fleet_section_callback',
            'fleet'
        );
    
        $locations = array('Ocean City', 'Somers Point', 'Avalon', 'New Gretna', 'Brick', 'Brigantine');
    
        foreach($locations as $i => $location) {
            add_settings_field(
                'fleet_field' . ($i + 1),
                __($location, 'fleet'),
                'fleet_field_callback',
                'fleet',
                'fleet_section',
                ['id' => $i + 1]
            );
        }
    }
    add_action('admin_init', 'fleet_settings_init');
    
    
    function fleet_section_callback($args) {
        echo '<p>Enter the URLs for the corresponding locations. These URLs will be used as links when a location is selected in a Fleet item.</p>';
        echo '<p>Use shortcode [fleet] to show the fleet cards on a page</p>';
    }

    function fleet_field_callback($args) {
        $options = get_option('fleet_options');
        $id = $args['id'];
        $url = isset($options["url$id"]) ? $options["url$id"] : '';
        ?>
        /<input type="text" id="url<?php echo $id; ?>" name="fleet_options[url<?php echo $id; ?>]" value="<?php echo esc_attr($url); ?>" placeholder="Location Page URL Path"><br><br>
        <?php
    }
    
    
// Create Shortcode
function fleet_shortcode($atts) {
    $args = array(
        'post_type' => 'fleet',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    );
    $fleet_query = new WP_Query($args);
    $options = get_option('fleet_options');
    $locations = [
        1 => 'Ocean City',
        2 => 'Somers Point',
        3 => 'Avalon',
        4 => 'New Gretna',
        5 => 'Brick',
        6 => 'Brigantine'
    ];

    ob_start();
    if ($fleet_query->have_posts()) {
        echo '<div class="fleet-buttons">';

// Add "All" button
echo '<button class="fleet-button active" data-location="all">All</button>';

// Add buttons for each location
foreach ($locations as $i => $location) {
    echo '<button class="fleet-button" data-location="' . $i . '">' . $location . '</button>';
}

echo '</div>';

echo '<div class="fleet-container">';

        while ($fleet_query->have_posts()) {
            $fleet_query->the_post();
            $model = get_post_meta(get_the_ID(), 'model', true);
            $capacity = get_post_meta(get_the_ID(), 'capacity', true);
            $loa = get_post_meta(get_the_ID(), 'loa', true);
            $locationMeta = [];
            $plus_fleetMeta = [];

            // Collect location and plus_fleet checkboxes
            for ($i = 1; $i <= 6; $i++) {
                $location_field = "location$i";
                $plus_fleet_field = "plus_fleet_location$i";

                $locationMeta[$i] = get_post_meta(get_the_ID(), $location_field, true) == 'on' ? true : false;
                $plus_fleetMeta[$i] = get_post_meta(get_the_ID(), $plus_fleet_field, true) == 'on' ? true : false;
            }

            // Display the post
            echo '<div class="fleet-item';
            foreach ($locationMeta as $i => $value) {
                if ($value) {
                    echo ' location-' . $i; // Add location class to the fleet item
                }
            }
            echo '">';

            echo '<h3>' . get_the_title() . '</h3>';
            if (!empty($model)) {
                echo '<h4>' . $model . '</h4>';
            }

            if (!empty($capacity)) {
                echo '<p>Capacity: ' . $capacity . '</p>';
            }

            if (!empty($loa)) {
                echo '<p>LOA: ' . $loa . '</p>';
            }

            echo '<p>Locations:</p><ul>';

            foreach ($locationMeta as $i => $value) {
                if ($value) {
                    $location_url = isset($options["url$i"]) ? $options["url$i"] : '';
                    echo '<li><a href="' . esc_url(get_site_url() . '/' . $location_url) . '">📍' . $locations[$i] . '</a></li>';

                    // Check if PLUS+ Fleet checkbox is selected for this location
                    if ($plus_fleetMeta[$i]) {
                        echo '<li><span class="plus_fleet_icon">🔹 PLUS+</span> ' . $locations[$i] . '</li>';
                    }
                }
            }

            echo '</ul><br>';

            if (has_post_thumbnail()) {
                the_post_thumbnail();
            }

            echo '</div>';
        }
        echo '</div>';
    }
    wp_reset_postdata();
    return ob_get_clean();
}


    add_shortcode('fleet', 'fleet_shortcode');

function fleet_enqueue_scripts() {
    wp_enqueue_script('jquery');


    // Add JavaScript code inline
    $script = "
			jQuery(document).ready(function($) {
    // Button click event
    $('.fleet-button').on('click', function() {
        var location = $(this).data('location');

        // Toggle active class on buttons
        $('.fleet-button').removeClass('active');
        $(this).addClass('active');

        // Show/hide fleet items based on the selected location
        if (location === 'all') {
            $('.fleet-item').show();
        } else {
            $('.fleet-item').hide();
            $('.location-' + location).show();
        }
    });
});

    ";
     wp_add_inline_script('jquery', $script);
}
add_action('wp_enqueue_scripts', 'fleet_enqueue_scripts');

    

// Add CSS
function fleet_add_styles() {
    ?>
    <style>
.fleet-buttons {
    text-align: center;
    margin-bottom: 1em;
    display: flex;
    flex-wrap: wrap; /* Allow the buttons to wrap */
    justify-content: center; /* Center the buttons horizontally */
}

.fleet-button {
    display: flex;
    flex: 0 0 auto; /* Fixed width of 200px */
    margin-right: 0.5em;
    margin-bottom: 0.5em;
    padding: 0.5em 1em;
    border: none;
    border-radius: 4px;
    background-color: #f1f1f1;
    cursor: pointer;
    font-size: 14px;
    color: inherit;
    text-decoration: none;
    align-items: center;
    justify-content: center;
}


.fleet-button:active,
.fleet-button.active,
.fleet-button:hover {
    color: #fff; 
	background-color: #000;
    text-decoration: none; /* Remove default underline */
}


.fleet-container {
    /*max-width: fit-content !important;*/
    display: flex;
    justify-content: space-evenly;
    flex-wrap: wrap;
    padding: 0 1em;
    box-sizing: border-box;
    margin-bottom: 1em;
}

.fleet-item {
    border: 1px solid #ddd;
    padding: 1em;
    margin: 0.5em;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
    background-color: white;
    box-sizing: border-box;
    line-height: inherit;
    flex: 0 1 calc(30% - 1em);
    text-align: left;
}

.fleet-item:hover {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    transform: translateY(-5px);
}

.fleet-item > * {
    margin: 0;
}

.fleet-item img {
    width: 100%;
    max-width: fit-content;
    height: auto;
    display: block;
    margin-bottom: 0.5em;
    object-fit: cover;
}

.fleet-item ul {
    padding-left: 1em;
    margin-bottom: 0;
    margin-top: 0;
    list-style-type: none;
}


    </style>
    <?php
}
add_action('wp_head', 'fleet_add_styles');
