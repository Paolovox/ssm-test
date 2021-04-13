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


$app->group('/dashboard', function (RouteCollectorProxy $groupDashboard) use ($auth) {

  // list
  $groupDashboard->get('', function (Request $request, Response $response) use ($auth, $crud) {
    $db = new dataBase();

    $user = $request->getAttribute('user');
    $p = $request->getQueryParams();

    if (!$user['idscuola']) {
      $user['idscuola'] = $p['idScuola'];
    }

    // Direttori
    $sql = sprintf( "SELECT count(*) as nc
      FROM ssm_utenti u
      LEFT JOIN ssm_utenti_ruoli_lista url ON url.idutente=u.id
      WHERE url.idscuola = '%s'
      AND url.idruolo = 5
      AND u.idstatus = 1", $db->real_escape_string( $user['idscuola'] ) );
    $db->query( $sql );
    $res['direttori_count'] = $db->fetchassoc()['nc'];


    // Studenti
    $sql = sprintf( "SELECT count(*) as nc
      FROM ssm_utenti u
      LEFT JOIN ssm_utenti_ruoli_lista url ON url.idutente=u.id
      WHERE url.idscuola = '%s'
      AND url.idruolo = 8
      AND u.idstatus = 1", $db->real_escape_string( $user['idscuola'] ) );
    $db->query( $sql );
    $res['specializzandi_count'] = $db->fetchassoc()['nc'];

    // Studenti senza registrazioni
    $sql = sprintf( "SELECT count(*) as nc
      FROM ssm_utenti u
      LEFT JOIN ssm_utenti_ruoli_lista url ON url.idutente=u.id
      LEFT JOIN (
        SELECT DISTINCT idutente FROM ssm_registrazioni
      ) as att ON att.idutente = u.id
      LEFT JOIN (
        SELECT DISTINCT idutente FROM ssm_registrazioni_np
      ) as atnp ON atnp.idutente = u.id
      WHERE url.idscuola = '%s'
      AND url.idruolo = 8
      AND u.idstatus = 1
      AND (atnp.idutente is null AND att.idutente is null)
      ORDER BY u.cognome,u.nome",
      $db->real_escape_string( $user['idscuola'] ) );
    $db->query( $sql );
    $res['specializzandi_no_reg'] = $db->fetchassoc()['nc'];


    // Tutor
    $sql = sprintf( "SELECT count(*) as nc
      FROM ssm_utenti u
      LEFT JOIN ssm_utenti_ruoli_lista url ON url.idutente=u.id AND url.idruolo = 7
      LEFT JOIN ssm_scuole_unita su ON su.idunita=url.idunita
      WHERE su.idscuola_specializzazione = '%s'
      AND url.idruolo = 7
      AND u.idstatus = 1", $db->real_escape_string( $user['idscuola'] ) );
    $db->query( $sql );
    $res['tutor_count'] = $db->fetchassoc()['nc'];
    // Tutor senza conferme registrazioni
    $sql = sprintf( "SELECT count(DISTINCT u.id) as tutor_no_val
      FROM
        ssm_utenti u
      LEFT JOIN ssm_utenti_ruoli_lista url ON url.idutente=u.id AND url.idruolo = 7
      LEFT JOIN ssm_scuole_unita su ON su.idunita=url.idunita
      LEFT JOIN (SELECT DISTINCT idtutor FROM ssm_turni WHERE idstatus=1) st ON u.id=st.idtutor
      LEFT JOIN (
        SELECT DISTINCT conferma_utente FROM ssm_registrazioni WHERE conferma_utente is not null AND conferma_idruolo = 7
      ) as t ON t.conferma_utente = u.id
      WHERE
        su.idscuola_specializzazione = '%s' AND t.conferma_utente IS NULL AND u.idstatus=1 AND st.idtutor is not null",
    $db->real_escape_string( $user['idscuola'] ) );
    $db->query( $sql );
    $res['tutor_no_val'] = $db->fetchassoc()['tutor_no_val'];

    if ( $user['idruolo'] >= 2 && $user['idruolo'] <= 4)    {
      $res['scuole'] = scuoleList($user['idateneo']);
    }

    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });


  // Specializzandi senza registrazioni (lista)
  $groupDashboard->get('/specializzandi_no', function (Request $request, Response $response) use ($auth, $crud) {
      $db = new dataBase();
      $log = new Logger();

      $user = $request->getAttribute('user');
      $idScuola = $user['idscuola'] ? $user['idscuola'] : $request->getQueryParams()['idScuola'];

      $sql = sprintf( "SELECT concat(u.nome,' ', u.cognome) as nome,
        u.matricola
      FROM ssm_utenti u
      LEFT JOIN ssm_utenti_ruoli_lista url ON url.idutente=u.id
      LEFT JOIN (
        SELECT DISTINCT idutente FROM ssm_registrazioni
      ) as att ON att.idutente = u.id
      LEFT JOIN (
        SELECT DISTINCT idutente FROM ssm_registrazioni_np
      ) as atnp ON atnp.idutente = u.id
      WHERE url.idscuola = '%s'
      AND url.idruolo = 8
      AND u.idstatus = 1
      AND (atnp.idutente is null AND att.idutente is null)
      ORDER BY u.cognome,u.nome",
      $db->real_escape_string( $idScuola ) );
    $log->log($sql);
    $db->query( $sql );

    $ar = [];
    while( $rec = $db->fetchassoc() ) {
      $ar[] = $rec;
    }

    $response->getBody()->write( json_encode( $ar ) );
    return $response;
  });


  // Tutor senza conferme (lista)
  $groupDashboard->get('/tutor_no', function (Request $request, Response $response) use ($auth, $crud) {
    $log = new Logger();
    $db = new dataBase();

    $user = $request->getAttribute('user');
    $idScuola = $user['idscuola'] ? $user['idscuola'] : $request->getQueryParams()['idScuola'];

    $sql = sprintf( "SELECT DISTINCT
      u.id,
      concat( u.nome, ' ', u.cognome ) AS nome 
      FROM
        ssm_utenti u
      LEFT JOIN ssm_utenti_ruoli_lista url ON url.idutente=u.id AND url.idruolo = 7
      LEFT JOIN ssm_scuole_unita su ON su.idunita=url.idunita
      LEFT JOIN (SELECT DISTINCT idtutor FROM ssm_turni WHERE idstatus=1) st ON u.id=st.idtutor
      LEFT JOIN (
        SELECT DISTINCT conferma_utente FROM ssm_registrazioni WHERE conferma_utente is not null AND conferma_idruolo = 7
      ) as t ON t.conferma_utente = u.id
      WHERE
        su.idscuola_specializzazione = '%s' AND t.conferma_utente IS NULL AND st.idtutor is not null AND u.idstatus=1
      ORDER BY
      u.cognome,
      u.nome",
      $db->real_escape_string( $idScuola ) );
    $log->log($sql);
    $db->query( $sql );

    $ar = [];
    while( $rec = $db->fetchassoc() ) {
      $ar[] = $rec;
    }

    $response->getBody()->write( json_encode( $ar ) );
    return $response;


  });

  // Grafici
  $groupDashboard->get('/graph_reg', function (Request $request, Response $response) use ($auth, $crud) {
    
    $db = new dataBase();

    $user = $request->getAttribute('user');
    $p = $request->getQueryParams();

    if (!$user['idscuola']) {
        $user['idscuola'] = $p['idScuola'];
    }

    // Grafico a barre
    $sql = sprintf("SELECT * FROM (SELECT COUNT(*) as num_reg, DATE(date_create) as data_reg
            FROM ssm_registrazioni
            WHERE idscuola_specializzazione='%s'
            GROUP BY DATE(date_create)
            ORDER BY DATE(date_create) DESC
            LIMIT 0, 10) as a
            ORDER BY a.data_reg ASC", $user['idscuola']);
    $db->query( $sql );
    $ar = [];
    while( $rec = $db->fetchassoc() ) {
      $ar['bar']['labels'][] = $rec['data_reg'];
      $ar['bar']['values']['data'][] = $rec['num_reg'];
    }

    // Grafico a torta (Tot registrazioni)
    $sql = sprintf("SELECT COUNT(*) as num_reg
            FROM ssm_registrazioni r
            WHERE idscuola_specializzazione='%s'AND conferma_stato=1", $db->real_escape_string( $user['idscuola'] ) );
    $db->query( $sql );
    while( $rec = $db->fetchassoc() ) {
      $ar['pie']['labels'][] = "Registrazioni";
      $ar['pie']['values'][] = $rec['num_reg'];
    }

    // Grafico a torta (Tot conferme)
    $sql = sprintf("SELECT COUNT(*) as num_reg
            FROM ssm_registrazioni r
            WHERE idscuola_specializzazione='%s'AND conferma_stato > 1", $db->real_escape_string( $user['idscuola'] ) );
    $db->query( $sql );
    while( $rec = $db->fetchassoc() ) {
      $ar['pie']['labels'][] = "Convalide tutor";
      $ar['pie']['values'][] = $rec['num_reg'];
    }

    $response->getBody()->write( json_encode( $ar ) );
    return $response;
  });



})->add( $authMW );






?>
