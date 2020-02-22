<?php

namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;


use Illuminate\Support\Facades\Storage;

use Validator;

class SpecialsController extends Controller
{


      public function api1(Request $request)
      {
        $check_iniziale = $request->all();
        if(count($check_iniziale) == 0){
          //NON è STATA FORMATTATA CORRETTAMENTE LA REQUEST INIZIALE
          $arr_ret= array([
                      "jsonrpc" => "2.0",
                      "error" => array([
                        "code"=> -32700,
                        "message"=> "Parse error"
                        ]),
                        "id" => 1
            ]);
            return response()->json($arr_ret);
        }

        $id = $request->input('id');
        $jsonrpc = $request->input('jsonrpc');
        $method = $request->input('method');
        $params = $request->input('params');
        if(count($params) == 0 ||  !isset($params) || !isset($jsonrpc) || !isset($method) || !isset($id) ||  !isset($jsonrpc) ){
          //UNO DI QUESTI PARAMETRI è NULLO
          $arr_ret= array([
                      "jsonrpc" => $jsonrpc,
                      "error" => array([
                        "code"=> -32600,
                        "message"=> "Invalid Request"
                        ]),
                        "id" => $id
            ]);
            return response()->json($arr_ret);
        }
        if($method != 'SearchNearestPharmacy'){
          // IL METODO NON ESISTE
          $arr_ret= array([
                      "jsonrpc" => $jsonrpc,
                      "error" => array([
                        "code"=> -32601,
                        "message"=> "Method not found"
                        ]),
                        "id" => $id
            ]);
          return response()->json($arr_ret);
        }
        $latitude = $params['currentLocation']['latitude'];
        $longitude = $params['currentLocation']['longitude'];
        $range = $params['range'];
        $limit = $params['limit'];

        if(!isset($latitude) ||  !isset($longitude) || !isset($range) || !isset($limit) || $jsonrpc != '2.0'){
          //UNO DI QUESTI PARAMETRI è NULLO
          $arr_ret= array([
                      "jsonrpc" => "2.0",
                      "error" => array([
                        "code"=> -32600,
                        "message"=> "Invalid Request"
                        ]),
                        "id" => $id
            ]);
            return response()->json($arr_ret);
        }


        $name = "Elenco-Farmacie.json";
        if(file_exists(public_path($name))){
            unlink(public_path($name));
          }
        $url = "https://dati.regione.campania.it/catalogo/resources/Elenco-Farmacie.geojson";

        //FACCIO IL DOWNLOAD DELL'ELENCO DELLE FARMACIE

        $contents = file_get_contents($url);
        Storage::put($name, $contents);
        $array_farmacie = array();

        //PRENDO I SINGOLI ELEMENTI JSON
        $element_json = json_decode($contents)->features;
        $i=0;
          foreach($element_json as $mydata)
          {
            $coordinate = $mydata->geometry->coordinates;
            $distanza = $this->distance( $coordinate[1],$coordinate[0], $latitude, $longitude);
            if($distanza < $range){
                //CARICO GLI ELEMENTI NELL'ARRAY SOLO QUANDO LA DISTANZA è MINORE DELLA DISTANZA
                $array_farmacie[$i++] = [
                                  "name" =>  $mydata->properties->Descrizione,
                                  "distance" =>  round($distanza),
                                  "location" => array(
                                                "latitude" => $coordinate[1],
                                                "longitude" =>$coordinate[0]
                                                )
                                ];
                              }
          }

        $array_farmacie_finali = array_column($array_farmacie, 'distance');

        array_multisort($array_farmacie_finali, SORT_ASC, $array_farmacie);
        //IN BASE AL LIMIT PRENDO I PRIMI VALORI DELL'ARRAY
        $array_da_inviare = array_slice($array_farmacie, 0, $limit) ;

        //COMPONGO L'ARRAY DI RITORNO
        $arr_ret= ([
                    "jsonrpc" => $jsonrpc,
                    "id" => $id,
                    "result" => array (
                              "pharmacies" => $array_da_inviare
                            ),
                  ]);


       return response()->json($arr_ret);
      }

      private function distance($lat1, $lon1, $lat2, $lon2) {
          $theta = $lon1 - $lon2;
          $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
          $miles = acos($miles);
          $miles = rad2deg($miles);
          $miles = $miles * 60 * 1.1515;
          $kilometers = $miles * 1.609344;
          return $kilometers;
      }


}
