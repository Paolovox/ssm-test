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
use \ottimis\phplibs\UUID;

// Clona
$app->group('/clona', function (RouteCollectorProxy $groupClona) use ($auth) {

    $groupClona->post('', function (Request $request, Response $response) use ($auth) {
      $db = new dataBase();
      $Utils = new Utils();
      $log = new Logger();

      $p = json_decode($request->getBody(), true);

      $log->log( "Clona - " . json_encode( $p ) );

      $idscuola_da = $p['idscuola_da'];
      $idscuola_a = $p['idscuola_a'];
      $idtipo = $p['idtipo'];

      switch( $idtipo ) {
        case 2: // Prestazioni
          clona_prestazioni( $idscuola_da, $idscuola_a );
          break;
        case 3: // Attivita
          clona_attivita( $idscuola_da, $idscuola_a );
          break;
        case 1: // ssm.ssm_registrazioni_attivita_np
          clona_registrazioni_attivita_np( $idscuola_da, $idscuola_a );
          break;
      }

      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'text/plain');
    });


});



function clona_prestazioni( $idfrom, $idto ) {
  $db = new dataBase();
  $log = new Logger();
  $uuid = new UUID();
  $Utils = new Utils();

  // recupera l'id della prestazione from
  $idprestazione_from = prestazione_get_id( $idfrom );
  $log->log( "Scuola FROM: " . $idfrom . " - prestazione from: " . $idprestazione_from );

  // recupera l'id della prestazione to
  $idprestazione_to = prestazione_get_id( $idto );
  $log->log( "Scuola TO: " . $idto . " - prestazione to: " . $idprestazione_to );

  // seleziona gli items del from
  $sql = sprintf( "SELECT *
    FROM ssm.ssm_registrazioni_combo_items
    WHERE idcombo='%s' AND idstatus=1", $db->real_escape_string( $idprestazione_from ) );
  $db->query( $sql );
  while( $rec = $db->fetchassoc() ) {
    $rec['id'] = $uuid->v4();
    $rec['idcombo'] = $idprestazione_to;
    $rec['idstatus'] = 1;
    $rec['date_create'] = "now()";
    $rec['date_update'] = "now()";
    $log->log( "prestazione: " . json_encode( $rec ) );

    $ret = $Utils->dbSql( true, "ssm.ssm_registrazioni_combo_items", $rec, "", "" );
    if( $ret['success'] != 1 ) {
      $log->log( json_encode( $ret ) );
      return 0;
    }
  }

  return 1;
}




function clona_attivita( $idfrom, $idto ) {
  $db = new dataBase();
  $log = new Logger();
  $uuid = new UUID();
  $Utils = new Utils();

  // recupera l'id della prestazione from
  $attivita_from = attivita_get_id( $idfrom );
  $log->log( "Scuola FROM: " . $idfrom . " - attivita: " . json_encode( $attivita_from ) );

  foreach( $attivita_from as $k => $attivita ) {

    // mantiene l'id del from
    $idattivita_from = $attivita['id'];

    $attivita['id'] = $uuid->v4();
    $attivita['idscuola'] = $idto;
    $attivita['idstatus'] = 1;
    $attivita['date_create'] = "now()";
    $attivita['date_update'] = "now()";
    unset( $attivita['elementi'] );

    $ret = $Utils->dbSql( true, "ssm.ssm_registrazioni_combo", $attivita, "", "" );
    if( $ret['success'] != 1 ) {
      $log->log( json_encode( $ret ) );
      return 0;
    }

    // seleziona gli items del from
    $sql = sprintf( "SELECT *
      FROM ssm.ssm_registrazioni_combo_items
      WHERE idcombo='%s' AND idstatus=1", $db->real_escape_string( $idattivita_from ) );
    $db->query( $sql );
    while( $rec = $db->fetchassoc() ) {
      $rec['id'] = $uuid->v4();
      $rec['idcombo'] = $attivita['id'];
      $rec['idstatus'] = 1;
      $rec['date_create'] = "now()";
      $rec['date_update'] = "now()";
      $log->log( "prestazione: " . json_encode( $rec ) );

      $ret = $Utils->dbSql( true, "ssm.ssm_registrazioni_combo_items", $rec, "", "" );
      if( $ret['success'] != 1 ) {
        $log->log( json_encode( $ret ) );
        return 0;
      }
    }

  }


  return 1;
}



function clona_registrazioni_attivita_np( $idfrom, $idto ) {
  $db = new dataBase();
  $db2 = new dataBase();

  $log = new Logger();
  $uuid = new UUID();
  $Utils = new Utils();


  $sql = sprintf( "SELECT * FROM ssm.ssm_scuole_attivita_np WHERE idscuola_specializzazione='%s' AND idstatus=1", $db->real_escape_string( $idfrom ) );
  $log->log( $sql );

  $db->query( $sql );
  while( $rec = $db->fetchassoc() ) {

    $log->log( "clona_registrazioni_attivita_np FROM: " . $idfrom . " - attivita: " . json_encode( $rec ) );

    // mantiene l'id del from
    $idattivita_from = $rec['id'];

    $rec['id'] = $uuid->v4();
    $rec['idscuola_specializzazione'] = $idto;
    $rec['idstatus'] = 1;
    $rec['date_create'] = "now()";
    $rec_dati['date_update'] = "now()";

    $ret = $Utils->dbSql( true, "ssm.ssm_scuole_attivita_np", $rec, "", "" );
    if( $ret['success'] != 1 ) {
      $log->log( json_encode( $ret ) );
      return 0;
    }

    // seleziona gli items del from
    $sql = sprintf( "SELECT *
      FROM ssm.ssm_scuole_attivita_np_dati
      WHERE idattivita='%s' AND idstatus=1", $idattivita_from );
    $db2->query( $sql );
    while( $rec_dati = $db2->fetchassoc() ) {
      $rec_dati['id'] = $uuid->v4();
      $rec_dati['idattivita'] = $rec['id'];
      $rec_dati['idstatus'] = 1;
      $rec_dati['date_create'] = "now()";
      $rec_dati['date_update'] = "now()";
      $log->log( "prestazione: " . json_encode( $rec_dati ) );

      $ret = $Utils->dbSql( true, "ssm.ssm_scuole_attivita_np_dati", $rec_dati, "", "" );
      if( $ret['success'] != 1 ) {
        $log->log( json_encode( $ret ) );
        return 0;
      }
    }
  }


  return 1;
}



function prestazione_get_id( $idscuola ) {
  $db = new dataBase();
  $sql = sprintf( "SELECT id FROM ssm.ssm_registrazioni_combo WHERE idscuola='%s' AND idtipo=1 AND idstatus=1", $db->real_escape_string( $idscuola ) );
  $db->query( $sql );
  $rec = $db->fetchassoc();
  return $rec['id'];
}


function attivita_get_id( $idscuola ) {
  $db = new dataBase();
  $sql = sprintf( "SELECT * FROM ssm.ssm_registrazioni_combo WHERE idscuola='%s' AND idtipo=2 AND idstatus=1", $db->real_escape_string( $idscuola ) );
  $db->query( $sql );
  while( $rec = $db->fetchassoc() ) {
    $ar[] = $rec;
  }
  return $ar;
}
