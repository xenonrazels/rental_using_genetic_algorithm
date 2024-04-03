<!-- <?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include Python script functions
    require_once 'path/to/your/python/functions.php';

    // Function to execute a Python script and return the output in a safer way
    function executePythonScript($scriptPath, $user_email) {
        $command = "python " . $scriptPath . " \"" . $user_email . "\"";
        $output = shell_exec($command);

        // Check if the output is not empty
        if ($output !== null) {
            return $output;
        } else {
            return ["error" => "Empty output from Python script."];
        }
    }

    // Get the username or email from the POST request
    $usernameOrEmail = $_POST["usernameOrEmail"];

    // Fetch the user's email from the database
    $user_email = fetch_user_email($usernameOrEmail); // Using the Python function to fetch user email

    // Check if user email is retrieved successfully
    if ($user_email) {
        // Call the Python script to get personalized recommendations
        $pythonScriptPath = "D:/WORK/major_proj_karuna/bikerental/src/final_model.py"; // Specify the path to your Python script
        $recommendations = executePythonScript($pythonScriptPath, $user_email);

        // Check if recommendations were obtained successfully
        if (isset($recommendations["error"])) {
            // Print error message if there was an issue with executing the Python script
            echo "Error: " . $recommendations["error"];
        } else {
            // Print JSON output
            echo $recommendations;
        }
    } else {
        echo "User not found."; // Handle the case where the user email is not found
    }
}

?> -->


<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include Python script functions
    require_once 'D:/WORK/major_proj_karuna/bikerental/src/recommendation.php';

    // Get the username or email from the POST request
    $usernameOrEmail = $_POST["usernameOrEmail"];

    // Fetch the user's email from the database
    $user_email = fetch_user_email($usernameOrEmail); // Using the Python function to fetch user email

    // Check if user email is retrieved successfully
    if ($user_email) {
        // Call the Python script to get personalized recommendations
        $recommendations = executePythonScript();

        // Check if recommendations were obtained successfully
        if (isset($recommendations["error"])) {
            // Print error message if there was an issue with executing the Python script
            echo "Error: " . $recommendations["error"];
        } else {
            // Check if recommendations array is empty
            if (empty($recommendations)) {
                echo "No recommendations found.";
            } else {
                // Print heading
                echo "<div class='container'>";
                echo "<h2 class='container-title'>Top 5 Recommendations</h2>";
                echo "</div>";
                // Print recommendations in separate styled HTML divs
                echo "<div class='recommendations-container'>";
                $count = 1;
                foreach ($recommendations as $recommendation) {
                    echo "<div class='recommendation'>";
                    echo "<h3 class='recommendation-title'>Recommendation number: <span class='underline-bold'>$count</span></h3>";
                    echo "<p><strong><span class='underline-bold'>VehicleId:</span></strong> <span class='recommendation-content'>" . $recommendation['VehicleId'] . "</span></p>";
                    echo "<p><strong><span class='underline-bold'>VehiclesTitle:</span></strong> <span class='recommendation-content'>" . $recommendation['VehiclesTitle'] . "</span></p>";
                    echo "<p><strong><span class='underline-bold'>PricePerDay(Npr):</span></strong> <span class='recommendation-content'>" . $recommendation['PricePerDay(Npr)'] . "</span></p>";
                    echo "<p><strong><span class='underline-bold'>AntiLockBrakingSystem:</span></strong> <span class='recommendation-content'>" . $recommendation['AntiLockBrakingSystem'] . "</span></p>";
                    echo "<p><strong><span class='underline-bold'>LeatherSeats:</span></strong> <span class='recommendation-content'>" . $recommendation['LeatherSeats'] . "</span></p>";
                    echo "</div>";
                    $count++;
                }
                echo "</div>"; // End of .recommendations-container
            }
        }
    } else {
        echo "User not found."; // Handle the case where the user email is not found
    }
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
</style>

