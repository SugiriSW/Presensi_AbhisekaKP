from flask import Flask, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)  # Mengizinkan semua domain mengakses

dummy_uid = "ABC123XYZ456"

@app.route('/get_uid', methods=['GET'])
def get_uid():
    return jsonify({"uID": dummy_uid})

if __name__ == '__main__':
    app.run(port=5000, debug=True)
