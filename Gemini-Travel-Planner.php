<?php
/*
Plugin Name: Gemini Travel Planner Text AI
Description: Text Only Input Gemini Travel Planner AI.
Version: 1.0
Author: Hamza Umer
Author URI: http://example.com
License: GPL2
*/

// Add a menu item to the dashboard for the plugin settings
function gemini_travel_planner_menu() {
    add_menu_page(
        'Gemini Travel Planner Settings',
        'Gemini Travel Planner',
        'manage_options',
        'gemini-travel-planner-settings',
        'gemini_travel_planner_settings_page'
    );
}
add_action('admin_menu', 'gemini_travel_planner_menu');

// Callback function for rendering the plugin settings page
function gemini_travel_planner_settings_page() {
    ?>
    <div class="wrap">
        <h2>Gemini Travel Planner Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('gemini_travel_planner_options'); ?>
            <?php do_settings_sections('gemini-travel-planner-settings'); ?>
            <input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>">
        </form>
    </div>
    <?php
}

// Register and define the settings
function gemini_travel_planner_register_settings() {
    register_setting('gemini_travel_planner_options', 'gemini_travel_planner_api_key');
    add_settings_section(
        'gemini_travel_planner_section',
        '',
        '',
        'gemini-travel-planner-settings'
    );
    add_settings_field(
        'gemini_travel_planner_api_key',
        'Gemini Travel Planner API Key',
        'gemini_travel_planner_api_key_callback',
        'gemini-travel-planner-settings',
        'gemini_travel_planner_section'
    );
}
add_action('admin_init', 'gemini_travel_planner_register_settings');

// Callback function for rendering the API key field
function gemini_travel_planner_api_key_callback() {
    $apiKey = get_option('gemini_travel_planner_api_key');
    echo '<input type="password" id="gemini_travel_planner_api_key" name="gemini_travel_planner_api_key" value="' . esc_attr($apiKey) . '" />';
}

// Function to generate content using Google API
function generate_content_using_google_api($source_city, $source_country, $destination_city, $destination_country, $start_date, $end_date, $budget, $language, $currency, $additional_notes) {
    // Get the Gemini Travel Planner API Key from settings
    $googleApiKey = get_option('gemini_travel_planner_api_key');

    // Check if API key is empty
    if (empty($googleApiKey)) {
        echo "Error: Gemini Travel Planner API Key is not set. Please set the API key in the plugin settings.";
        return;
    }

    // // Prepare the request data
    // $requestData = array(
    //     "contents" => array(
    //         array(
    //             "parts" => array(
    //                 array(
    //                     "text" => "Create a detailed travel itinerary in $language focused on attractions, restaurants, and activities for a trip from $source_city, $source_country to $destination_city, $destination_country, starting on $start_date and ends on $end_date, within a budget of $currency $budget. This should include daily timings, preferences for accommodations, a travel style.Consider the $additional_notes. Also, provide a travel checklist relevant to the destination and duration."
    //                 )
    //             )
    //         )
    //     )
    // );

    // Prepare the request data
    $requestData = array(
        "contents" => array(
        array(
            "parts" => array(
            array(
                "text" => "**Plan a trip itinerary for me!**\n\n**User Information:**\n* **Origin:** ${source_city}, ${source_country}\n* **Destination:** ${destination_city}, ${destination_country}\n* **Travel Dates:** ${start_date} to ${end_date} (flexible +/- 2 days if needed)\n* **Budget:** Total trip budget of ${budget} in ${currency}\n* **Language Preference:** ${language}\n* **Additional Notes:** ${additional_notes} (e.g., interests, travel style, dietary restrictions)\n\n**Itinerary Details:**\n* **Flights:** Search for roundtrip flights within the budget and flexible date range. Prioritize options with minimal layovers.\n* **Accommodation:** Suggest hotels or alternative accommodations that fit the budget and user preferences (e.g., hostels for budget travelers, luxury hotels for high-end budgets).\n* **Activities:** Curate a daily itinerary with a mix of cultural experiences, historical landmarks, natural attractions, and activities based on user interests and additional notes. Consider including free and ticketed options.\n* **Transportation:** Recommend local transportation options like public transport passes, car rentals (if applicable), or walking tours depending on user preferences.\n* **Visas and Currency Exchange:** Briefly mention any visa requirements and suggest reputable currency exchange options. \n\n**Additional Considerations:**\n* **Weather:** Briefly mention the typical weather conditions during the travel dates.\n* **Safety:** Provide basic safety tips for the destination.\n\n**Output Format:**\n* Present the itinerary in a clear and concise daily format with estimated costs for each component.\n* Include links to relevant resources for booking flights, accommodation, and activities.\n\n**Important Notes:**\n* Double-check official sources for visa requirements and currency exchange rates.\n* Adjust the prompt as needed based on user preferences and the capabilities of Gemini-Pro."
            )
            )
        )
        )
    );

    // Convert request data to JSON
    $jsonData = json_encode($requestData);

    // Set up the request arguments
    $args = array(
        'headers'     => array(
            'Content-Type' => 'application/json',
        ),
        'body'        => $jsonData,
        'timeout'     => 30,
    );

    // Set the URL with the API key
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $googleApiKey;

    // Make the request
    $response = wp_remote_post($url, $args);

    // Check if request was successful
    if (!is_wp_error($response) && $response['response']['code'] == 200) {
        // Get the response body
        $body = wp_remote_retrieve_body($response);
        // Decode the JSON response
        $data = json_decode($body, true);
        // Check if data is valid
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            // Extract and display the text
            $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];
            echo $generatedText;
        } else {
            echo "Error: Unable to retrieve generated text from API response.";
        }
    } else {
        // Handle error
        if (is_wp_error($response)) {
            echo "Error: " . $response->get_error_message();
        } else {
            echo "Error: " . $response['response']['code'];
        }
    }
}

function enqueue_bootstrap() {
    // Enqueue Bootstrap CSS
    wp_enqueue_style('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css', array(), '4.5.2');
}
add_action('wp_enqueue_scripts', 'enqueue_bootstrap');

function enqueue_jquery() {
    wp_enqueue_script('jquery');
}
add_action('wp_enqueue_scripts', 'enqueue_jquery');

// Shortcode to display the travel planner form
function gemini_travel_planner_form_shortcode() {
    ob_start();
    ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <form id="gemini-travel-planner-form" method="post">
                    <div class="form-group">
                        <label for="source_city">Source City</label>
                        <input type="text" class="form-control" id="source_city" name="source_city" required>
                    </div>
                    <div class="form-group">
                        <label for="source_country">Source Country</label>
                        <input type="text" class="form-control" id="source_country" name="source_country" required>
                    </div>
                    <div class="form-group">
                        <label for="destination_city">Destination City</label>
                        <input type="text" class="form-control" id="destination_city" name="destination_city" required>
                    </div>
                    <div class="form-group">
                        <label for="destination_country">Destination Country</label>
                        <input type="text" class="form-control" id="destination_country" name="destination_country" required>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>
                    <div class="form-group">
                        <label for="budget">Budget</label>
                        <input type="number" class="form-control" id="budget" name="budget" required>
                    </div>
                    <div class="form-group">
                        <label for="language">Language</label>
                        <select class="form-control" id="language" name="language" required>
                            <option value="english">English</option>
                            <option value="spanish">Spanish</option>
                            <option value="french">French</option>
                            <!-- Add more languages as needed -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="currency">Currency</label>
                        <select class="form-control" id="currency" name="currency" required>
                            <option value="USD">USD - United States Dollar</option>
                            <option value="PKR">PKR - Pakistani Rupee</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="GBP">GBP - British Pound Sterling</option>
                            <option value="JPY">JPY - Japanese Yen</option>
                            <!-- Add more currencies as needed -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="additional_notes">Additional Notes</label>
                        <textarea class="form-control" id="additional_notes" name="additional_notes" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>

    <div id="gemini-travel-planner-result"></div>

    <script>
    jQuery(document).ready(function($) {
        $('#gemini-travel-planner-form').submit(function(e) {
            e.preventDefault();
            var formData = $(this).serialize();
            $.ajax({
                type: 'POST',
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                data: formData + '&action=generate_gemini_travel_planner_content',
                success: function(response) {
                    $('#gemini-travel-planner-result').html(response);
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('gemini_travel_planner_form', 'gemini_travel_planner_form_shortcode');


function generate_content_using_google_api_callback() {
    if (isset($_POST['source_city']) && isset($_POST['source_country']) && isset($_POST['destination_city']) && isset($_POST['destination_country']) && isset($_POST['start_date']) && isset($_POST['end_date']) && isset($_POST['budget']) && isset($_POST['language']) && isset($_POST['currency']) && isset($_POST['additional_notes'])) {
        $source_city = sanitize_text_field($_POST['source_city']);
        $source_country = sanitize_text_field($_POST['source_country']);
        $destination_city = sanitize_text_field($_POST['destination_city']);
        $destination_country = sanitize_text_field($_POST['destination_country']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $budget = sanitize_text_field($_POST['budget']);
        $language = sanitize_text_field($_POST['language']);
        $currency = sanitize_text_field($_POST['currency']);
        $additional_notes = sanitize_text_field($_POST['additional_notes']);
        
        // Generate content using the Gemini API
        generate_content_using_google_api($source_city, $source_country, $destination_city, $destination_country, $start_date, $end_date, $budget, $language, $currency, $additional_notes);
    }
    exit;
}

add_shortcode('gemini_travel_planner_form', 'gemini_travel_planner_form_shortcode');
add_action('wp_ajax_generate_gemini_travel_planner_content', 'generate_content_using_google_api_callback');
add_action('wp_ajax_nopriv_generate_gemini_travel_planner_content', 'generate_content_using_google_api_callback');
?>