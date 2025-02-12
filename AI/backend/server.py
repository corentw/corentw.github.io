from flask import Flask, request, jsonify
from model import analyze_image
from flask_cors import CORS

app = Flask(__name__)
CORS(app)  # Erlaubt Anfragen von Frontend

@app.route('/analyze', methods=['POST'])
def analyze():
    file = request.files['file']
    result = analyze_image(file)
    return jsonify({'result': result})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
