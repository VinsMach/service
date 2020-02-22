per mandare in esecuzione questa applicazione :
1) scaricare l'app
2) mandare in esecuzione
3) con un normalissimo rest client effettuare le chiamate:
  3.1) POST http://127.0.0.1:8000/api
        {
          "id": 1,
          "jsonrpc": "2.0",
          "method": "SearchNearestPharmacy",
          "params": {
            "currentLocation": {
              "latitude": 41.10938993,
              "longitude": 15.032101
            },
            "range": 50,
            "limit": 2
          }
          }
