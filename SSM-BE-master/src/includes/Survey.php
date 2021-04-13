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




$app->group('/survey', function (RouteCollectorProxy $groupSurvey) use ($auth) {

  $data = [
    "table" => "ssm_survey",
    "id" => "id",
    "sort" => "titolo",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm_survey.id", "ssm_survey.titolo", "ssm_survey_status.status" ],
    "list_join" => [
      [
          "ssm_survey_status",
          " ssm_survey_status.id=ssm_survey.idstatus_survey "
      ]
    ]

  ];


  $crud = new CRUD( $data );

  // list
  $groupSurvey->get('', function (Request $request, Response $response) use ($auth, $crud) {
      $db = new dataBase();
      $log = new Logger();

      $user = $request->getAttribute('user');
      $p = $request->getQueryParams();
      $log->log( "survey user: " . json_encode( $user ) );

      $q[] = "ssm_survey.idstatus= 1";
      $q[] = "ssm_survey.idstatus_survey= 1";

      if( $user['idruolo'] != 2 && $user['idruolo'] != 9 ) {
        $q[] = sprintf( "date(now()) >= ssm_survey.data_inizio AND date(now()) <= ssm_survey.data_fine" );
      }

      // tutor
      if( $user['idruolo'] == 7 ) {
        $q[] = "JSON_CONTAINS(somministrata_a, '7', '\$')=1";

        // se è un tutor cerca l'id della scuola nei turni di tutoraggio
        // e usa la variabile $user per sovrascrivere
        $user['idscuola'] = scuola_tutor_get( $user['id'] );
      }


      // specializzando
      if( $user['idruolo'] == 8 ) {
        $q[] = "JSON_CONTAINS(somministrata_a, '8', '\$')=1";
      }

      if( $p['s'] != "" ) {
        $q[] = sprintf( "ssm_survey.titolo LIKE '%%%s%%'", $db->real_escape_string( $p['s'] ) );
      }

      if( $user['idruolo'] != 2 ) {
        $q[] = sprintf( "ssm_survey.idscuola='%s'", $db->real_escape_string( $user['idscuola'] ) );
      }


      $sql = sprintf( "SELECT SQL_CALC_FOUND_ROWS
            ssm_survey.id, ssm_survey.titolo, ssm_survey_status.status,
            date_format( data_inizio, '%%d-%%m-%%Y' ) as data_inizio,
            date_format( data_fine, '%%d-%%m-%%Y' ) as data_fine
          FROM ssm_survey
          LEFT JOIN ssm_survey_status ON ssm_survey_status.id=ssm_survey.idstatus_survey
          WHERE %s
          ORDER BY %s %s
          LIMIT %s, %s",
        implode( " AND ", $q ),
        $db->real_escape_string( $p['srt'] ), $db->real_escape_string( $p['o'] ),
        ($p['p']-1) * $p['c'], $p['c'] );

      $log->log( "survey list: " . $sql );

      $res = [];
      $db->query( $sql );
      while( $rec = $db->fetchassoc() ) {
        $surveyData = survey_check( $rec['id'], $user['id'] );

        if( $titolo == "" ) {
          $titolo = $rec['titolo'];
        }

        switch( $user['idruolo'] ) {
          case 8: // specializzando
          case 7: // tutor
            $rec['canEditDomande'] = false;
            $rec['canRispondi'] = true;
            $rec['canViewRisposte'] = false;
            $rec['canViewRisposteSpecializzando'] = true;
            $rec['canViewReport'] = false;
            break;

          case 9: // segreteria
          case 2: // direttore ateneo
            $rec['canEditDomande'] = true;
            $rec['canViewRisposte'] = true;
            $rec['canDelete'] = true;
            $rec['canModify'] = true;
            $res['segreteria'] = 1;
            $rec['canViewReport'] = true;
            $rec['canViewRisposteSpecializzando'] = false;

            break;
        }

        $rec['idrisposta'] = $surveyData['id'];
        $rec['data_risposta'] = $surveyData['data_risposta'];
        $rec['ora_risposta'] = $surveyData['ora_risposta'];

        $res['rows'][] = $rec;
      }


      $db->query( "SELECT FOUND_ROWS()" );
      $resCount = $db->fetcharray();
      $res['total'] = $resCount[0];
      $res['count'] = sizeof( $ar );

      // possono creare nuove survey solo Segreteria e Direttore Ateneo
      if( $user['idruolo'] == 9 || $user['idruolo'] == 2 ) {
        $res['segreteria'] = 1;
        $rec['canModify'] = true;
      }



      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $groupSurvey->get('/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();

      $user = $request->getAttribute('user');

      $res = $crud->record_get( $args['id'] )['data'][0];

      if( $res['somministrata_a'] != "" )
        $res['somministrata_a'] = json_decode( $res['somministrata_a'], true );
      else
        $res['somministrata_a'] = [];

      $ar = array( "table" => "ssm_survey_status", "value" => "id", "text" => "status", "order" => "id", "" );
      $res['status_list'] = $Utils->_combo_list( $ar, true, "" );

      // se l'utente è la segreteria della scuola, l'uunica scuola selezionabile è
      // quella dell'utente
      $log->log( "SURVEY - idruolo - " . $user['idruolo'] );
      if( $user['idruolo'] == 2 ) {
        $res['scuole_list'] = scuoleList( $user['idateneo'] );
        $res['canSelectScuola'] = true;
      } else {
        $res['canSelectScuola'] = false;
      }

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupSurvey->put('', function (Request $request, Response $response) use ($crud ) {

    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $user = $request->getAttribute('user');

    $log->log( "NEW SURVEY: " . json_encode( $user ) );

    // se è la segreteria prende la scuola dell'utente
    // altrimenti prende quella indicata nella selecr
    if( $user['idruolo'] == 9 ) {
      $p['idscuola'] = $user['idscuola'];
    } else {
      $p['idscuola'] = $p['idscuola'];
    }


    $p['somministrata_a'] = json_encode( $p['somministrata_a'] );

    $retValidate = validate( "survey", $p );
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
  $groupSurvey->post('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $log->log( "MODIFICA SURVEY" );

    $p = json_decode($request->getBody(), true);
    $p['somministrata_a'] = json_encode( $p['somministrata_a'] );


    $retValidate = validate( "survey", $p );
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
  $groupSurvey->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
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


function survey_check( $idsurvey, $iduser ) {
  $db = new dataBase();
  $log = new Logger();

  $sql = sprintf( "SELECT id,
    DATE_FORMAT( sur.data_risposta, '%%d-%%m-%%Y') as data_risposta,
    DATE_FORMAT( sur.data_risposta, '%%H:%%i:%%s') as ora_risposta
   FROM ssm_survey_risposte sur
   WHERE idsurvey='%s'
   AND iduser='%s'
   LIMIT 0,1", $db->real_escape_string( $idsurvey ), $db->real_escape_string( $iduser ) );
  $log->log( "check - " . $sql );
  $db->query( $sql );
  $rec = $db->fetchassoc();
  if( $rec['id'] == "" )
    $rec['id'] = 0;
  return $rec;
}

function scuola_tutor_get( $idtutor ) {
  $db = new dataBase();
  $sql = sprintf( "SELECT idscuola FROM ssm_turni WHERE idtutor='%s' AND idstatus=1", $db->real_escape_string( $idtutor ) );
  $db->query( $sql );
  $rec = $db->fetchassoc();
  if( $rec['idscuola'] == "" )
    return "0";
  return $rec['idscuola'];
}

?>
