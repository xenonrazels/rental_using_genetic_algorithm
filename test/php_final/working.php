<?php

// Function to execute a Python script and return the output in a safer way
function executePythonScript($scriptPath, $username) {
    // Escape username to prevent command injection
    $escapedUsername = escapeshellarg($username);
    
    // Construct the command with the username parameter
    $command = "python " . $scriptPath . " " . $escapedUsername;
    
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
        return ["error" => "Something Went Wrong ! $jsonOutput"];
    }
}

// Check if the username is provided via GET method
if (isset($_GET['username'])) {
    // Retrieve the username from the GET parameters
    $username = $_GET['username'];

    // Call the Python script for data preprocessing, recommendation, and formatting
    $pythonScriptPath = "D:/WORK/major_proj_karuna/bikerental/src/personalized_recommendation.py"; // Specify the path to your Python script
    
    try {
        $recommendations = executePythonScript($pythonScriptPath, $username);

        // Check if recommendations were obtained successfully
        if (isset($recommendations["error"])) {
            // Print error message if there was an issue with executing the Python script
            echo "<div class='error-container'>";
            echo "<h1 class='error-heading'>Error: " . $recommendations["error"] . "</h1>";
            echo "</div>";
        } else {
            // Check if recommendations array is empty
            if (empty($recommendations)) {
                echo "No recommendations found for the user: $username";
            } else {
                // Print heading
                echo "<div class='container'>";
                echo "<h2 class='container-title'>Top Recommendations for User: $username</h2>";
                echo "</div>";
                // Print recommendations in separate styled HTML divs
                echo "<div class='recommendations-container'>";
                $count = 1;
                foreach ($recommendations as $recommendation) {
                    if ($count <= 5) {
                        echo "<div class='recommendation'>";
                        echo "<h3 class='recommendation-title'>Recommendation number: <span class='underline-bold'>$count</span></h3>";
                        echo "<p><strong><span class='underline-bold'>VehicleId:</span></strong> <span class='recommendation-content'>" . $recommendation['VehicleId'] . "</span></p>";
                        echo "<p><strong><span class='underline-bold'>VehiclesTitle:</span></strong> <span class='recommendation-content'>" . $recommendation['VehiclesTitle'] . "</span></p>";
                        echo "<p><strong><span class='underline-bold'>PricePerDay(Npr):</span></strong> <span class='recommendation-content'>" . $recommendation['PricePerDay(Npr)'] . "</span></p>";
                        // Print additional recommendation fields if needed
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
    } catch (Exception $e) {
        // Print error message for any other exceptions occurred
        echo "<div class='error-container'>";
        echo "<h1 class='error-heading'>An error occurred: " . $e->getMessage() . "</h1>";
        echo "</div>";
    }
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

<style>
    .container {
        margin: 0 auto;
        text-align: center;
    }

    .container-title {
        font-weight: bold;
        text-decoration: underline;
    }

    .recommendations-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
    }

    .recommendation {
        border: 1px solid #ccc;
        border-radius: 5px;
        padding: 10px;
        width: calc(50% - 20px); /* Adjust width to fit two divs in a row with gap */
        box-sizing: border-box; /* Include padding and border in the width calculation */
        text-align: left; /* Align text inside the recommendation div to the left */
    }

    .recommendation p {
        margin: 10px 0; /* Add space between paragraphs */
    }

    .recommendation-title {
        text-align: center; /* Center-align the title */
        font-weight: bold;
        text-decoration: underline;
    }

    .recommendation-content {
        display: block;
        text-align: center; /* Center-align the content */
    }

    .underline-bold {
        font-weight: bold;
    }

    /* Style for big buttons */
    .big-button {
        background-color: #007bff; /* Blue theme */
        color: white;
        border: none;
        border-radius: 5px;
        padding: 10px 20px;
        font-size: 18px;
        cursor: pointer;
        margin-top: 10px;
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
        border: 1px solid #007bff;
        border-radius: 5px;
        font-size: 16px;
        width: 300px;
        margin-right: 10px;
    }

    .get-recommendation-button {
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 10px 20px;
        font-size: 16px;
        cursor: pointer;
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
</style>
