<?php
/*
Plugin Name: Google Avis Dynamiques
Description: Récupère automatiquement les avis Google d'un lieu donné via l'API Google Places.
Version: 1.0
Author: Diaa Al Hariri
*/

// Fonction pour obtenir le Place ID via une recherche textuelle
function get_place_id_by_text_search($api_key, $query = 'Put your company name here') {
    $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?' . http_build_query([
        'query' => $query,
        'key' => $api_key
    ]);

    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['results'][0]['place_id'])) {
        return $data['results'][0]['place_id'];
    }

    return false;
}

// Fonction pour récupérer les avis Google via l'API Place Details
function get_google_reviews($place_id, $api_key, $max_results = 5) {
    $url = "https://maps.googleapis.com/maps/api/place/details/json?" . http_build_query([
        'place_id' => $place_id,
        'fields' => 'reviews,rating,user_ratings_total',
        'key' => $api_key
    ]);

    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return 'Une erreur s’est produite lors de la récupération des données depuis Google.';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['result']['reviews'])) {
        $output = '<div class="google-reviews">';
        $reviews = array_slice($data['result']['reviews'], 0, $max_results);
        foreach ($reviews as $review) {
            $author = esc_html($review['author_name']);
            $rating = intval($review['rating']);
            $text = esc_html($review['text']);
            $time = date("d/m/Y", $review['time']);

            $output .= "<div class='review'>";
            $output .= "<p><strong>$author</strong> — $time</p>";
            $output .= "<p>⭐️ " . str_repeat('★', $rating) . "</p>";
            $output .= "<p>$text</p>";
            $output .= "</div><hr>";
        }
        $output .= '</div>';
        return $output;
    } else {
        return 'Aucun avis disponible.';
    }
}

// Shortcode [google_reviews_dynamic]
add_shortcode('google_reviews_dynamic', function() {

    // Mettez votre clé API Google ici (put your api key here)
    $api_key = 'PUT_YOUR_API_KEY_HERE'; 
    
    // Mettez le nom ou l'adresse de votre entreprise ici
    $query = 'Put your company name here';

    $place_id = get_place_id_by_text_search($api_key, $query);
    if (!$place_id) {
        return 'Impossible de trouver un identifiant de lieu (Place ID) valide pour cet endroit.';
    }

    return get_google_reviews($place_id, $api_key, 5);
});
