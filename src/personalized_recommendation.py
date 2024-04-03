import sys
import re
import pymysql
import pandas as pd
import random
import numpy as np
from deap import base, creator, tools, algorithms
import json


def is_valid_email(email):
    """
    Checks if the provided string is a valid email address.
    """
    # Regular expression pattern for email validation
    pattern = r'^[\w\.-]+@[\w\.-]+\.\w+$'
    return re.match(pattern, email) is not None

def fetch_user_email(username):
    """
    Fetches the user_email associated with the provided username or email from the database.
    """
    connection = None  # Initialize connection variable
    try:
        # Check if the provided username is an email address
        if is_valid_email(username):
            return username

        # Connect to the MySQL database
        connection = pymysql.connect(host='127.0.0.1',
                                     port=3306,
                                     user='root',
                                     password='',
                                     db='bikerental')

        # Create a cursor object
        with connection.cursor() as cursor:
            # Execute a SQL query to retrieve the user_email
            query = "SELECT EmailId FROM tblusers WHERE FullName = %s"
            cursor.execute(query, (username,))
            
            # Fetch the user_email
            result = cursor.fetchone()
            if result:
                user_email = result[0]
                return user_email  # Return the user email if found

            # If no user email is found
            print("User not found.")
            return None

    except pymysql.Error as e:
        print(f"Error: {e}")

    finally:
        # Close the connection
        if connection:
            connection.close()

    print("An error occurred while fetching user email.")
    return None

def preprocess_data(user_email):
    """
    Connects to a MySQL database, retrieves vehicle data associated with the user,
    preprocesses it, and returns a pandas DataFrame.
    """

    # Connect to the MySQL database
    connection = pymysql.connect(host='127.0.0.1',
                                 port=3306,
                                 user='root',
                                 password='',
                                 db='bikerental')

    # Create a cursor object
    cursor = connection.cursor()

    # Execute a SQL query to retrieve the relevant features based on user's email
    query = f"SELECT v.id, v.VehiclesTitle, v.VehiclesBrand, v.PricePerDay, v.FuelType, v.ModelYear, v.SeatingCapacity, \
                     v.AirConditioner, v.PowerDoorLocks, v.AntiLockBrakingSystem, v.BrakeAssist, v.PowerSteering, \
                     v.DriverAirbag, v.PassengerAirbag, v.PowerWindows, v.CDPlayer, v.CentralLocking, v.CrashSensor, \
                     v.LeatherSeats \
              FROM tblvehicles v \
              JOIN tblbooking b ON v.id = b.VehicleId \
              WHERE b.userEmail = '{user_email}'"
              
    cursor.execute(query)

    # Fetch all the rows as a list of tuples
    rows = cursor.fetchall()

    # Create a pandas DataFrame from the list of tuples
    df = pd.DataFrame(rows, columns=[
                      'id', 'VehiclesTitle', 'VehiclesBrand', 'PricePerDay', 'FuelType', 'ModelYear', 'SeatingCapacity',
                      'AirConditioner', 'PowerDoorLocks', 'AntiLockBrakingSystem', 'BrakeAssist', 'PowerSteering',
                      'DriverAirbag', 'PassengerAirbag', 'PowerWindows', 'CDPlayer', 'CentralLocking', 'CrashSensor',
                      'LeatherSeats'])

    # Preprocess data (example: handle missing values)
    df.fillna(random.choice([0, 1]), inplace=True)  # Replace null values with random choice (0 or 1)

    # Convert boolean columns to integers (optional)
    bool_columns = ['AirConditioner', 'PowerDoorLocks', 'AntiLockBrakingSystem', 'BrakeAssist', 'PowerSteering',
                    'DriverAirbag', 'PassengerAirbag', 'PowerWindows', 'CDPlayer', 'CentralLocking', 'CrashSensor',
                    'LeatherSeats']
    df[bool_columns] = df[bool_columns].astype(int)

    # Close the cursor and the connection
    cursor.close()
    connection.close()

    # Return the preprocessed DataFrame
    return df

def recommendation_function(preprocessed_data):
    # Determine the count of vehicles retrieved from the database
    num_vehicles = len(preprocessed_data)

    # Set population size based on the count of vehicles
    population_size = min(num_vehicles, 10)  # Limit population size to a maximum of 10

    # Define the fitness function
    def evaluate(individual):
        score = 0
        relevant_features = ['PricePerDay', 'AntiLockBrakingSystem', 'CrashSensor', 'CentralLocking', 'LeatherSeats']
        for i in range(len(individual)):
            if individual[i] == 1:
                for feature in relevant_features:
                    score += preprocessed_data.loc[i, feature]
        return (score,)

    # Create the individual class and population
    creator.create("FitnessMax", base.Fitness, weights=(1.0,))
    creator.create("Individual", list, fitness=creator.FitnessMax)
    toolbox = base.Toolbox()
    toolbox.register("attr_bool", random.randint, 0, 1)
    toolbox.register("individual", tools.initRepeat, creator.Individual, toolbox.attr_bool, n=num_vehicles)
    toolbox.register("population", tools.initRepeat, list, toolbox.individual)
    toolbox.register("evaluate", evaluate)
    toolbox.register("mate", tools.cxTwoPoint)
    toolbox.register("mutate", tools.mutFlipBit, indpb=0.05)
    toolbox.register("select", tools.selTournament, tournsize=3)

    # Initialize population
    population = toolbox.population(n=population_size)

    # Run the genetic algorithm
    hof = tools.HallOfFame(1)
    stats = tools.Statistics(lambda ind: ind.fitness.values)
    stats.register("avg", np.mean)
    stats.register("min", np.min)
    stats.register("max", np.max)
    population, logbook = algorithms.eaSimple(population, toolbox, cxpb=0.7, mutpb=0.2, ngen=100, stats=stats,
                                              halloffame=hof, verbose=True)

    # Return the best individual
    best_individual = hof[0]

    # Select only relevant mutated children for recommendation
    relevant_children = [preprocessed_data.loc[i] for i, val in enumerate(best_individual) if val == 1]

    # Return only the top 10 recommendations
    return relevant_children

def format_recommendations(recommendations):
    formatted_recommendations = []
    for rec in recommendations[:5]:
        formatted_rec = {
            "VehicleId": int(rec['id']),
            "VehiclesTitle": rec['VehiclesTitle'],
            "PricePerDay(Npr)": round(float(rec['PricePerDay']), 2),
            "AirConditioner": 'Yes' if rec['AirConditioner'] == 1 else 'No',
            "AntiLockBrakingSystem": 'Yes' if rec['AntiLockBrakingSystem'] == 1 else 'No',
            "DriverAirbag": 'Yes' if rec['DriverAirbag'] == 1 else 'No',
            "PassengerAirbag": 'Yes' if rec['PassengerAirbag'] == 1 else 'No',
            "LeatherSeats": 'Yes' if rec['LeatherSeats'] == 1 else 'No'
        }
        formatted_recommendations.append(formatted_rec)
    return formatted_recommendations

def save_recommendations_to_database(username, recommendations):
    """
    Saves recommendations for a specific user to the database.
    """
    connection = None
    try:
        # Connect to the MySQL database
        connection = pymysql.connect(host='127.0.0.1',
                                     port=3306,
                                     user='root',
                                     password='',
                                     db='bikerental')

        # Create a cursor object
        with connection.cursor() as cursor:
            # Convert recommendations to JSON format
            recommendations_json = json.dumps(recommendations)
            
            # Execute SQL query to insert recommendations
            sql = "INSERT INTO user_recommendations (username, recommendations_json) VALUES (%s, %s)"
            cursor.execute(sql, (username, recommendations_json))

        # Commit changes
        connection.commit()

    except pymysql.Error as e:
        print(f"Error: {e}")

    finally:
        # Close the connection
        if connection:
            connection.close()

def get_recommendation(username):
    user_email = fetch_user_email(username)
    if user_email:
        data = preprocess_data(user_email)
        recommendations = recommendation_function(data)
        formatted_recommendations = format_recommendations(recommendations)
        save_recommendations_to_database(username, formatted_recommendations)
        return json.dumps(formatted_recommendations)  # Convert recommendations to JSON format
    else:
        return json.dumps({"error": "User not found."})  # Return error message in JSON format


if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python script_name.py username")
        sys.exit(1)

    username = sys.argv[1]
    print(get_recommendation(username))



    
