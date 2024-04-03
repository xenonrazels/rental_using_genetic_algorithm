<?php

function getRecommendationsFromFlask() {
    // URL of the Flask server
    $flaskServerUrl = "http://localhost:5000/recommendations"; // Adjust the URL if Flask is running on a different port or host
    
    // Make a GET request to the Flask server
    $curl = curl_init($flaskServerUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);
    
    // Check if the response is valid JSON
    $recommendations = json_decode($response, true);
    if ($recommendations !== null) {
        // Return the recommendations
        return $recommendations['recommendations'];
    } else {
        return null;
    }
}

// Example usage:
$recommendations = getRecommendationsFromFlask();

// Check if recommendations are obtained successfully
if ($recommendations !== null) {
    // Print recommendations
    foreach ($recommendations as $recommendation) {
        echo "VehicleId: " . $recommendation['VehicleId'] . "\n";
        echo "VehiclesTitle: " . $recommendation['VehiclesTitle'] . "\n";
        echo "PricePerDay(Npr): " . $recommendation['PricePerDay(Npr)'] . "\n";
        echo "AirConditioner: " . $recommendation['AirConditioner'] . "\n";
        echo "AntiLockBrakingSystem: " . $recommendation['AntiLockBrakingSystem'] . "\n";
        echo "DriverAirbag: " . $recommendation['DriverAirbag'] . "\n";
        echo "PassengerAirbag: " . $recommendation['PassengerAirbag'] . "\n";
        echo "LeatherSeats: " . $recommendation['LeatherSeats'] . "\n";
        echo "\n";
    }
} else {
    echo "Failed to get recommendations.";
}

?>
