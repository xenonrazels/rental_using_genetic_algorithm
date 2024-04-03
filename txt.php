<?php

// Define the base/root directory dynamically
function get_root_directory() {
    $current_dir = __DIR__;
 
    while (!file_exists($current_dir . '/src/personalized_recommendation.py') && dirname($current_dir) !== $current_dir) {
        $current_dir = dirname($current_dir);
    }
   
    return $current_dir . '\\';
}

// Function to execute a Python script and return the output in a safer way
function executePythonScript($scriptPath, $username) {
    // Get the root directory dynamically
    $root_directory = get_root_directory();
   

    // Escape username to prevent command injection
    $escapedUsername = escapeshellarg($username);
    
    
    // Construct the command with the username parameter
    $command = "python " . $root_directory . "bikerental/src/personalized_recommendation.py " . $escapedUsername;
    echo($command);
    // Execute the command and capture the output
    $output = shell_exec($command);

    // Extract JSON portion from output
    $jsonStart = strpos($output, '[');
    $jsonEnd = strrpos($output, ']');
    $jsonOutput = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);

    // Check if the extracted portion is valid JSON
    $decodedOutput = json_decode($jsonOutput, true);
    if ($decodedOutput !== null) {
        return $decodedOutput;
    } else {
        return ["error" => "Something Went Wrong !"];
    }
}

// Function to get recommendations using the Python script
function getRecommendations($username) {
    // Call the Python script to get recommendations
    $recommendations = executePythonScript("", $username);

    return $recommendations;
}

// Function to validate user and then show recommendations
function get_recommendation($username) {
    // Call the function to get recommendations
    $recommendations = getRecommendations($username);

    // Check if recommendations were obtained successfully
    if (isset($recommendations["error"])) {
        // Print error message if there was an issue with getting recommendations
        echo "<div class='error-container'>";
        echo "<h1 class='error-heading'>Error: " . $recommendations["error"] . "</h1>";
        echo "</div>";
    } else {
        // Connect to the MySQL database
        $connection = new PDO("mysql:host=127.0.0.1;port=3306;dbname=bikerental", "root", "");

        // Print recommendations
        echo "<div class='container'>";
        echo "<h2 class='container-title'>Top Recommendations for User: $username</h2>";
        echo "</div>";
        echo "<div class='recommendations-container'>";

        $count = 1;
        foreach ($recommendations as $recommendation) {
            if ($count <= 5) {
                echo "<div class='recommendation'>";
                echo "<h3 class='recommendation-title'>Recommendation <span class='underline-bold'></span></h3>";
                echo "<p><strong><span class='underline-bold'>VehiclesTitle:</span></strong> <span class='recommendation-content'>" . $recommendation['VehiclesTitle'] . "</span></p>";

                // Fetch details from the MySQL database for the current recommendation
                $sql = "SELECT * FROM tblvehicles WHERE VehiclesTitle = :vehiclesTitle";
                $query = $connection->prepare($sql);
                $query->bindParam(':vehiclesTitle', $recommendation['VehiclesTitle'], PDO::PARAM_STR);
                $query->execute();
                $result = $query->fetch(PDO::FETCH_ASSOC);

                // Display the fetched details
                if ($result) {
                    echo "<img src='admin/img/vehicleimages/{$result['Vimage1']}' alt='image'>";
                    echo "<p>Fuel Type: {$result['FuelType']}</p>";
                    echo "<p>Model Year: {$result['ModelYear']} Model</p>";
                    echo "<p>Seating Capacity: {$result['SeatingCapacity']} seats</p>";
                    echo "<p>Price Per Day: {$result['PricePerDay']}</p>";
                    // Add more details as needed
                }

                echo "</div>";
                $count++;
            } else {
                break;
            }
        }
        
        echo "</div>"; // End of .recommendations-container
        
        // Button to return to the input field for new recommendations
        echo "<div class='container'>";
        echo "<form action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "' method='get'>";
        echo "<input class='big-button' type='submit' value='Get New Recommendations'>";
        echo "</form>";
        echo "</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recommendation System</title>

<style>
    body {
        background-color: #f2f2f2; /* Set a light background color */
        font-family: Arial, sans-serif; /* Change font family for better readability */
        margin: 0; /* Remove default margin */
        padding: 0; /* Remove default padding */
    }

    .container {
        max-width: 1200px; /* Set maximum width for the container */
        margin: 20px auto; /* Center the container horizontally and add space at the top and bottom */
        text-align: center;
        padding: 20px;
        background-color: #fff; /* Set a white background color for the container */
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Add a subtle shadow effect */
    }

    .container-title {
        font-weight: bold;
        text-decoration: underline;
        color: #333; /* Set a darker text color */
        font-size: 24px; /* Increase font size for the title */
    }

    .recommendations-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
    }

    .recommendation {
        border: 2px solid #007bff; /* Increase border thickness and change color */
        border-radius: 10px;
        padding: 20px;
        width: calc(50% - 20px); /* Adjust width to fit two divs in a row with gap */
        box-sizing: border-box; /* Include padding and border in the width calculation */
        text-align: center; /* Center-align the content */
        background-color: #fff; /* Set a white background color */
        margin-bottom: 20px; /* Reduce margin bottom */
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Add a subtle shadow effect */
    }

    .recommendation p {
        margin: 20px 0; /* Add space between paragraphs */
        color: #333; /* Set a darker text color */
        font-size: 16px; /* Adjust font size */
    }

    .recommendation-title {
        font-weight: bold;
        text-decoration: none; /* Remove underline */
        color: #007bff; /* Set a primary color for emphasis */
        font-size: 20px; /* Increase font size */
    }

    .recommendation-content {
        font-weight: bold;
        font-size: 18px;
        color: #333; /* Set a darker text color */
    }

    .underline-bold {
        font-weight: bold;
    }

    /* Style for big buttons */
    .big-button {
        background-color: #007bff; /* Blue theme */
        color: white;
        border: none;
        border-radius: 30px; /* Make the button more rounded */
        padding: 15px 30px;
        font-size: 18px;
        cursor: pointer;
        margin-top: 20px;
        display: inline-block;
        text-decoration: none;
        transition: background-color 0.3s; /* Add a smooth transition effect */
    }

    .big-button:hover {
        background-color: #0056b3; /* Darken the button color on hover */
    }

    /* Style for input field and button */
    .input-container {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 20px;
    }

    .input-field {
        padding: 10px;
        border: 2px solid #007bff; /* Increase border thickness and change color */
        border-radius: 30px; /* Make the input field more rounded */
        font-size: 16px;
        width: 300px;
        margin-right: 10px;
    }

    .get-recommendation-button {
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 30px; /* Make the button more rounded */
        padding: 15px 30px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s; /* Add a smooth transition effect */
    }

    .get-recommendation-button:hover {
        background-color: #0056b3; /* Darken the button color on hover */
    }
    
    /* Style for error messages */
    .error-container {
        margin: 20px auto;
        text-align: center;
    }
    
    .error-heading {
        font-size: 24px;
        color: red;
    }

    /* Additional styles for car listing */
    .col-list-3 {
        width: calc(50% - 20px); /* Adjust width and margin for better spacing */
        margin-bottom: 20px; /* Reduce margin bottom */
        text-align: center; /* Center-align the content */
    }

    .recent-car-list {
        border: 2px solid #007bff; /* Increase border thickness and change color */
        border-radius: 10px;
        padding: 20px;
        background-color: #fff; /* Set a white background color */
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Add a subtle shadow effect */
    }

    .car-info-box ul {
        list-style-type: none;
        padding: 0;
    }

    .car-info-box ul li {
        margin-bottom: 10px;
    }

    .car-title-m h6 {
        margin: 20px 0;
        color: #333; /* Set a slightly darker text color */
        font-size: 20px; /* Increase font size */
    }

    .price {
        font-size: 24px; /* Increase font size */
        font-weight: bold;
        color: #007bff; /* Set a primary color for emphasis */
    }

    .inventory_info_m p {
        margin: 20px 0;
        color: #666; /* Set a slightly darker text color */
        font-size: 16px; /* Adjust font size */
    }
</style>




</head>
<body>

<?php

// Check if the username is provided via GET method
if (isset($_GET['username'])) {
    // Retrieve the username from the GET parameters
    $username = $_GET['username'];

    // Call the function to validate user and show recommendations
    get_recommendation($username);
} else {
    // Display the input field if username is not provided
    echo "<div class='container'>";
    echo "<h2>Get Recommendations</h2>";
    echo "<form action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "' method='get'>";
    echo "<div class='input-container'>";
    echo "<input class='input-field' type='text' name='username' placeholder='Enter Username'>";
    echo "<button class='get-recommendation-button' type='submit'>Get Recommendations</button>";
    echo "</div>";
    echo "</form>";
    echo "</div>";
}

?>

</body>
</html>
