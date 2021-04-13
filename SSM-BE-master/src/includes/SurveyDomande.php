<?php



// Slim framework
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy as RouteCollectorProxy;
use Slim\Exception\HttpNotFoundException as HttpNotFoundException;
use Slim\Factory\AppFactory;

use \ottimis\phplibs\Logger;
use \ottimis\phplibs\Utils;
use \ottimis\phplibs\dataBase;
use \ottimis\phplibs\Auth;




$app->group('/survey_domande', function (RouteCollectorProxy $groupSurveyDomande) use ($auth) {

  $data = [
    "table" => "ssm_survey_domande",
    "id" => "id",
    "sort" => "domanda",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm_survey_domande.id",
        "ssm_survey_domande.domanda",
        "ssm_survey_tipi_risposta.tipo_risposta",
        "if( obbligatorio=1, 'Sì', 'No' ) as obbligatorio_text",
        "ssm_survey_status.status",
       ],
    "list_join" => [
      [
          "ssm_survey_status",
          " ssm_survey_status.id=ssm_survey_domande.idstatus_domanda "
      ],
      [
          "ssm_survey_tipi_risposta",
          " ssm_survey_tipi_risposta.id=ssm_survey_domande.idtipo_risposta "
      ]
    ]

  ];


  $crud = new CRUD( $data );

  // list
  $groupSurveyDomande->get('/{idsurvey}', function (Request $request, Response $response, $args) use ($auth, $crud) {
      $user = $request->getAttribute('user');
      $p = $request->getQueryParams();

      $p['_ssm_survey_domande.idstatus'] = "1";
      $p['_ssm_survey_domande.idsurvey'] = $args['idsurvey'];

      if( $p['s'] != "" ) {
        $p['search'] = array(
          "field" => "ssm_survey_domande.domanda",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%"
        );
      }

      $res = $crud->record_list( $p );
      if( !$res )
        $res = [];

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $groupSurveyDomande->get('/{idsurvey}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $res = $crud->record_get( $args['id'] )['data'][0];

      $res['risposte'] = json_decode( $res['risposte'], true );

      $ar = array( "table" => "ssm_survey_status", "value" => "id", "text" => "status", "order" => "id", "" );
      $res['status_list'] = $Utils->_combo_list( $ar, true, "" );

      $ar = array( "table" => "ssm_survey_tipi_risposta", "value" => "id", "text" => "tipo_risposta", "order" => "id", "" );
      $res['tipi_list'] = $Utils->_combo_list( $ar, true, "" );

      $response->getBody()->write( json_encode( $res, JSON_NUMERIC_CHECK ) );
      return $response;
  });

  // new
  $groupSurveyDomande->put('/{idsurvey}', function (Request $request, Response $response, $args) use ($crud ) {
    $p = json_decode($request->getBody(), true);
    $user = $request->getAttribute('user');

    $p['idsurvey'] = $args['idsurvey'];
    $p['risposte'] = json_encode( $p['risposte'] );

    $retValidate = validate( "survey_domande", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $res = $crud->record_new( $p );
    if( $res['success'] == 1 ) {
      $response->getBody()->write( json_encode( $res ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write( "Errore aggiornamento" );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }

    return $response;
  });


  //update
  $groupSurveyDomande->post('/{idsurvey}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "UPDATE: " . json_encode( $p ) );

    if( is_array( $p['risposte'] ) )
      $p['risposte'] = json_encode( $p['risposte'] );

    //$p['risposte'] = json_encode( $p['risposte'] );

    $retValidate = validate( "survey_domande", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $res = $crud->record_update( $args['id'], $p );
    if( $res['success'] == 1 ) {
      $response->getBody()->write( json_encode( $res ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write( "Errore aggiornamento" );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }
  });

  // delete
  $groupSurveyDomande->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $res = $crud->record_delete( $args['id'] );
    if( $res['success'] == 1 ) {
      $response->getBody()->write( json_encode( $res ) );
        return $response
          ->withStatus(200)
          ->withHeader('Content-Type', 'application/json');
    } else {
        $response->getBody()->write( "Errore aggiornamento" );
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
    }
  });



})->add($authMW);



?>
