# %%
import pandas as pd
import pymysql
import random


def preprocess_data():
    """
    Connects to a MySQL database, retrieves vehicle data,
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

    # Execute a SQL query to retrieve the relevant features
    cursor.execute('SELECT id, VehiclesTitle, VehiclesBrand, PricePerDay, FuelType, ModelYear, SeatingCapacity, AirConditioner, PowerDoorLocks, AntiLockBrakingSystem, BrakeAssist, PowerSteering, DriverAirbag, PassengerAirbag, PowerWindows, CDPlayer, CentralLocking, CrashSensor, LeatherSeats FROM tblvehicles')

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
    df['AirConditioner'] = df['AirConditioner'].astype(int)
    df['PowerDoorLocks'] = df['PowerDoorLocks'].astype(int)
    df['AntiLockBrakingSystem'] = df['AntiLockBrakingSystem'].astype(int)
    df['BrakeAssist'] = df['BrakeAssist'].astype(int)
    df['PowerSteering'] = df['PowerSteering'].astype(int)
    df['DriverAirbag'] = df['DriverAirbag'].astype(int)
    df['PassengerAirbag'] = df['PassengerAirbag'].astype(int)
    df['PowerWindows'] = df['PowerWindows'].astype(int)
    df['CDPlayer'] = df['CDPlayer'].astype(int)
    df['CentralLocking'] = df['CentralLocking'].astype(int)
    df['CrashSensor'] = df['CrashSensor'].astype(int)
    df['LeatherSeats'] = df['LeatherSeats'].astype(int)

    # Close the cursor and the connection
    cursor.close()
    connection.close()

    # Return the preprocessed DataFrame
    return df


# %%


# %%
import random
import numpy as np
from deap import base, creator, tools, algorithms

def recommendation_function(preprocessed_data):
    # Define the fitness function
    def evaluate(individual):
        score = 0
        for i in range(len(individual)):
            if individual[i] == 1:
                # Assuming 'PricePerDay' is a relevant feature for score calculation
                score += preprocessed_data.loc[i, 'PricePerDay']
        return (score,)
    
    # Create the individual class and population
    creator.create("FitnessMax", base.Fitness, weights=(1.0,))
    creator.create("Individual", list, fitness=creator.FitnessMax)
    toolbox = base.Toolbox()
    toolbox.register("attr_bool", random.randint, 0, 1)
    toolbox.register("individual", tools.initRepeat, creator.Individual, toolbox.attr_bool, n=len(preprocessed_data))
    toolbox.register("population", tools.initRepeat, list, toolbox.individual)
    toolbox.register("evaluate", evaluate)
    toolbox.register("mate", tools.cxTwoPoint)
    toolbox.register("mutate", tools.mutFlipBit, indpb=0.05)
    toolbox.register("select", tools.selTournament, tournsize=3)
    
    # Initialize population
    population = toolbox.population(n=50)
    
    # Run the genetic algorithm
    hof = tools.HallOfFame(1)
    stats = tools.Statistics(lambda ind: ind.fitness.values)
    stats.register("avg", np.mean)
    stats.register("min", np.min)
    stats.register("max", np.max)
    population, logbook = algorithms.eaSimple(population, toolbox, cxpb=0.7, mutpb=0.2, ngen=100, stats=stats, halloffame=hof, verbose=True)
    
    # Return the best individual
    best_individual = hof[0]
    recommendation = [preprocessed_data.loc[i] for i, val in enumerate(best_individual) if val == 1]
    
    # Return only the top 10 recommendations
    return recommendation


import json

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

def get_recommendation():
    data = preprocess_data()
    recommendations = recommendation_function(data)
    return recommendations

# Call get_recommendation to get recommendations
recommendations = get_recommendation()

# Format recommendations and print JSON output
formatted_recommendations = format_recommendations(recommendations)
json_output = json.dumps(formatted_recommendations)
print(json_output)


