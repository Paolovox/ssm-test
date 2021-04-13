<?php

use \ottimis\phplibs\Logger;

class CASLogin
{
    protected $casAuthenticationUrl = "https://cas.unimi.it/ws/authentication?wsdl";
    protected $casAuthorizationnUrl = "https://cas.unimi.it/ws/authorization?wsdl";

    private function parseResult($result)   {
        switch ($result) {
            case 0:
                $ret = true;
                break;
            default:
                $ret = false;
                break;
        }
        return $ret;
    }

    function grantServiceTicket($token)
    {
        //Set up the parameters array. This array will be the argument for the SOAP call.
        $param = array(
                "in0" => $token,
                "in1" => "https://unimi.specializzazionemedica.it/login"
            );


        $options = [
            'stream_context' => stream_context_create([
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel… Gecko/20100101 Firefox/77.0',
                'http'=> [
                    'header' => "Accept: *"
                    ]
                ]
            )
        ];

        $wsdl = $this->casAuthenticationUrl;
        //Soap client
        $client = new SoapClient($wsdl, $options);
        //do the call to Encrypt method
        try {
            $objectResult = $client->decodeTicketGrantingTicket($param);
            $parsedXml = simplexml_load_string($objectResult);
            return $this->parseResult($parsedXml);
        }
        //catch SOAP exceptions
        catch (SoapFault $fault) {
            die($fault->getMessage());
        }
    }

    function validateServiceTicket($token) {
        //Set up the parameters array. This array will be the argument for the SOAP call.
        $param = array(
            "in0" => $token,
            "in1" => "https://unimi.specializzazionemedica.it/login"
        );

        $options = [
            'stream_context' => stream_context_create(
                [
                'user_agent' => 'PHP/SOAP',
                'http'=> [
                    'header' => "Accept: application/xml\r\n
                                X-WHATEVER: something"
                    ]
                ]
            )
        ];


        $wsdl = $this->casAuthenticationUrl;
        //Soap client
        $client = new SoapClient($wsdl);
        //do the call to Encrypt method
        try {
            $objectResult = $client->validateServiceTicket($param);
            $object = json_decode( json_encode( $objectResult ), true );
            if( isset( $object['out']['chainedAuthenticationBeans']['AuthenticationBean']['unimiPrincipalBean']['employeeNumber'] ) ) {
              return array(
                "success" => 1,
                "user" => $object['out']['chainedAuthenticationBeans']['AuthenticationBean']['unimiPrincipalBean']
              );
            } else {
                return array("success" => false);
            }
        }
        //catch SOAP exceptions
        catch (SoapFault $fault) {
            $log = new Logger();
            $log->log(json_encode($fault->getMessage()));
            return array("success" => false);
        }
    }

    function destroyToken($token)
    {
        //Set up the parameters array. This array will be the argument for the SOAP call.
        $param = array(
                "in0" => $token
            );

        $wsdl = $this->casAuthenticationUrl;
        //Soap client
        $client = new SoapClient($wsdl, array("cache_wsdl" => WSDL_CACHE_NONE));
        //do the call to Encrypt method
        try {
            $objectResult = $client->destroyTicketGrantingTicket($param);
            $parsedXml = simplexml_load_string($objectResult);
            print_r($parsedXml);
        }
        //catch SOAP exceptions
        catch (SoapFault $fault) {
            var_dump($fault->getMessage());
            die($fault->getMessage());
        }
    }

    function decodeToken($token) {
        //Set up the parameters array. This array will be the argument for the SOAP call.
        $param = array(
            "in0" => $token
        );

        $wsdl = $this->casAuthenticationUrl;
        //Soap client
        $client = new SoapClient($wsdl);
        //do the call to Encrypt method
        try {
            $objectResult = $client->decodeTicketGrantingTicket($param);
            $parsedXml = simplexml_load_string($objectResult);
            print_r($parsedXml);
        }
        //catch SOAP exceptions
        catch (SoapFault $fault) {
            die($fault->getMessage());
        }
    }
}
