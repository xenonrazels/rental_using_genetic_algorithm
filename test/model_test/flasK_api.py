from flask import Flask, jsonify
from final_model import preprocess_data, recommendation_function, format_recommendations

app = Flask(__name__)

# Define route for getting recommendations
@app.route('/recommendations', methods=['GET'])
def get_recommendations():
    # Preprocess the data
    data = preprocess_data()
    # Get recommendations
    recommendations = recommendation_function(data)
    # Format recommendations
    formatted_output = format_recommendations(recommendations)
    # Return formatted recommendations as JSON
    return jsonify({'recommendations': formatted_output})

# Prevent running the app in Jupyter Notebook


def run_flask_app():
    if __name__ == '__main__' and '__file__' not in globals():
        app.run(debug=True)

run_flask_app()