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


$app->group('/pds', function (RouteCollectorProxy $groupPds) use ($auth, $args) {


  $data = [
    "table" => "ssm.ssm_pds",
    "id" => "id",
    "sort" => "ss.nome",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create",
    "list_fields" => [ "ssm.ssm_pds.id", "ss.nome as nome_settore", "ssm_pds.anno", "ta.nome_tipologia", "quantita", "ad.nome as ambito_text"],
    "list_join" => [
      [
          "ssm.ssm_settori_scientifici ss",
          " ss.id=ssm.ssm_pds.idsettore_scientifico "
      ],
      [
          "ssm.ssm_tipologie_attivita ta",
          " ta.id=ssm.ssm_pds.idtipologia_attivita "
      ],
      [
          "ssm.ssm_pds_ambiti_disciplinari ad",
          " ad.id=ssm.ssm_pds.idambito_disciplinare "
      ],
    ]
  ];


  $crud = new CRUD( $data );



  // list
  $groupPds->get('/{idscuola_specializzazione}/{idcoorte}', function (Request $request, Response $response, $args) use ($auth, $crud) {
      $log = new Logger();

      $log->log( "Piano studi:" . json_encode( $args) );
      $p = $request->getQueryParams();

      if ($p['srt'] == "nome_settore") {
        $p['srt'] = "ss.nome";
      }

      if( $p['s'] != "" ) {
      $p['multi_search'] = array(
        [
          "field" => "ss.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%",
          "operatorAfter" => " OR "
        ],
        [
          "field" => "ad.nome",
          "operator" => " LIKE ",
          "value" => "%" . $p['s'] . "%"
        ]
      );
    }

      $p['_ssm.ssm_pds.idstatus'] = 1;
      $p['_idscuola_specializzazione'] = $args['idscuola_specializzazione'];
      $p['_idcoorte'] = $args['idcoorte'];
      $res = $crud->record_list( $p );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get - lista ambiti disciplinari
  $groupPds->get('/{idscuola_specializzazione}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();
      $log = new Logger();
      $db = new dataBase();

      $log->log( "get - " . $args['idscuola_specializzazione'] . " - " . $args['id'] );

      $res = $crud->record_get( $args['id'] )['data'][0];
      //$res['idtipologie_attivita'] = json_decode( $res['idtipologie_attivita'] );



      // cerca la tipologia formativa del settore selezionato
      $sql = sprintf( "SELECT ss.idpds_classe
      FROM ssm.ssm_scuole_atenei sa
      LEFT JOIN ssm.ssm_scuole ss ON ss.id=sa.idscuola
      WHERE sa.idstatus=1 AND ss.idstatus=1 AND sa.id='%s'", $db->real_escape_string( $args['idscuola_specializzazione'] ) );
      $db->query( $sql );
      $rec = $db->fetchassoc();
      $log->log( "tipologia_cfu - classe: " . json_encode( $rec ) );

      // estrae
      $sql = sprintf( "SELECT DISTINCT sad.id, sad.nome as text
        FROM ssm.ssm_pds_ambiti_disciplinari sad
        LEFT JOIN ssm.ssm_scuole_pds_obiettivi spo ON spo.idambito=sad.id
        WHERE sad.idclasse='%s'
          AND sad.idstatus=1
          AND spo.idscuola_specializzazione='%s'
          AND spo.idstatus=1
        ORDER BY sad.nome",
      $db->real_escape_string( $rec['idpds_classe'] ),
      $db->real_escape_string( $args['idscuola_specializzazione'] )
       );
      $db->query( $sql );
      $log->log( "tipologia_cfu - ambito: " . json_encode( $recAmbito ) );
      $log->log( "tipologia_cfu - ambito sql: " . $sql );

      $res['ambiti_disciplinari_list'] = [];
      while( $recAmbito = $db->fetchassoc() ) {
        $res['ambiti_disciplinari_list'][] = $recAmbito;
      }

      $res['settori_scientifici_list'] = settori_scientifici_list( $args['idscuola_specializzazione'], $res['idambito'] );
      $res['anni_list'] = getAnniScuola($args['idscuola_specializzazione']);

/***

      $arSelect = $Utils->dbSelect( [
        "log" => true,
        "select" => ["sc.idpds_classe"],
        "from" => "ssm.ssm_scuole_atenei",
        "where" => [
          [
          "field" => "ssm.ssm_scuole_atenei.id",
          "operator" => "=",
          "value" => $args['idscuola_specializzazione']
          ]
        ],
        "join" => [
          [
            "ssm.ssm_scuole sc",
            "sc.id=ssm.ssm_scuole_atenei.idscuola"
          ]
        ]
      ]);


      $sql = sprintf( "SELECT DISTINCT ss.id, concat( ss.nome, ' - ', sad.nome ) AS text
        FROM
          ssm.ssm_pds_ambiti_disciplinari sad
          LEFT JOIN ssm.ssm_settori_scientifici ss ON JSON_CONTAINS(
            sad.idsettori_scientifici, %s )
          LEFT JOIN ssm.ssm_scuole_pds_obiettivi spo ON spo.idambito=sad.id
        WHERE sad.idclasse='%s'
          AND sad.idstatus=1
          AND ss.idstatus = 1
          AND spo.idstatus=1
          AND spo.idscuola_specializzazione='%s'
        ORDER BY text",
          "concat('\"', ss.id, '\"')",
          $arSelect['data'][0]['idpds_classe'],
          $args['idscuola_specializzazione']
      );
      $log->log( "Settori scientifici:" . $sql );
      $db->query( $sql );
      while( $rec = $db->fetchassoc() ) {
        $arSS[] = $rec;
      }
      $res['settori_scientifici_list'] = $arSS;
**/

/***
      $log->log( "arSelect " . json_encode( $arSelect ) );

      $arSelect2 = $Utils->dbSelect( [
        "log" => true,
        "select" => ["idsettori_scientifici"],
        "from" => "ssm.ssm_pds_ambiti_disciplinari",
        "where" => [
          [
          "field" => "idclasse",
          "operator" => "=",
          "value" => $arSelect['data'][0]['idpds_classe'],
          "operatorAfter" => " AND "
          ],
          [
            "field" => "idstatus",
            "operator" => "=",
            "value" => 1,
          ]
        ],
      ]);

      $log->log( "Classe: " . $sql );

      $log->log( "ssm.ssm_pds_ambiti_disciplinari " . json_encode( $arSelect2 ) );


      $arSettori = array();
      foreach( $arSelect2['data'] as $k => $v ) {
        $ar = json_decode( $v['idsettori_scientifici'], true );
        $arSettori = array_merge( $arSettori, $ar );
      }

      $log->log( "arSettori: " . json_encode( $arSettori ) );

      $s = implode( "','", $arSettori );
      //$where = sprintf( "id IN ('%s')", $s );
      //$log->log( $where );
      //$ar = array( "table" => "ssm.ssm_settori_scientifici", "value" => "id", "text" => "concat( nome, '**')", "order" => "id", "where" => $where );
      //$res['settori_scientifici_list'] = $Utils->_combo_list( $ar, true, "" );

      $arSS = [];
      $where = sprintf( "'%s'", $s );
      $sql = sprintf( "SELECT DISTINCT ss.id, concat(ss.nome, ' - ', sad.nome) as text
        FROM ssm.ssm_pds_ambiti_disciplinari sad
        LEFT JOIN ssm.ssm_settori_scientifici ss ON JSON_CONTAINS(sad.idsettori_scientifici, %s)
        WHERE ss.id IN (%s) AND ss.idstatus=1 AND sad.idclasse='%s'
        ORDER BY text",
        "concat('\"', ss.id, '\"')", $where,
        $arSelect['data'][0]['idpds_classe']
      );
      $log->log( "Settori scientifici:" . $sql );
      $db->query( $sql );
      while( $rec = $db->fetchassoc() ) {
        $arSS[] = $rec;
      }
      $res['settori_scientifici_list'] = $arSS;
*/

      // prendo la tipologia della scuola
      // prendo gli ambiti disciplinari di quella tipologia

      /*
      $ar = array( "table" => "ssm.ssm_tipologie_attivita", "value" => "id", "text" => "nome_tipologia", "order" => "id" );
      $res['tipologie_attivita_list'] = $Utils->_combo_list( $ar, true, "" );

      $log->log( "tipologie_attivita_list - " . json_encode( $res ) );
      */


      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $groupPds->put('/{idscuola_specializzazione}/{idcoorte}', function (Request $request, Response $response, $args) use ($crud ) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $log->log( "put - " . json_encode( $args ) . " - " . json_encode( $p ) );

    $p['idscuola_specializzazione'] = $args['idscuola_specializzazione'];
    $p['idcoorte'] = $args['idcoorte'];


    $retValidate = validate( "ssm.ssm_scuole_pds_obiettivi", $p );
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
  $groupPds->post('/{idscuola_specializzazione}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    //$p['idtipologie_attivita'] = json_encode( $p['idtipologie_attivita'] );

    $retValidate = validate( "ssm.ssm_scuole_pds_obiettivi", $p );
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
  $groupPds->delete('/{idscuola_speciaizzazione}/{idcoorte}/{id}', function (Request $request, Response $response, $args) use ($crud) {
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



  // get
  $groupPds->get('/settori_scientifici/{idscuola_specializzazione}/{idcoorte}/{idambito}', function (Request $request, Response $response, $args) use ($crud) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $log->log( "get - " . json_encode( $args ) );

    $res['settori_scientifici_list'] = settori_scientifici_list( $args['idscuola_specializzazione'], $args['idambito'] );
    $response->getBody()->write( json_encode( $res ) );
    return $response;
  });


  // get
  $groupPds->get('/tipologia_cfu/{idscuola_specializzazione}/{idcoorte}/{idambito}/{idsettore_scientifico}', function (Request $request, Response $response, $args) use ($crud) {
    $Utils = new Utils();
    $log = new Logger();
    $db = new dataBase();

    $log->log( "/tipologia_cfu/{idscuola_specializzazione}/{idcoorte}/{idambito}/{idsettore_scientifico} - " . json_encode( $args ) );


    // cerca la tipologia formativa del settore selezionato
    $sql = sprintf( "SELECT ss.idpds_classe
      FROM ssm.ssm_scuole_atenei sa
      LEFT JOIN ssm.ssm_scuole ss ON ss.id=sa.idscuola
      WHERE sa.idstatus=1 AND ss.idstatus=1 AND sa.id='%s'", $db->real_escape_string( $args['idscuola_specializzazione'] ) );
    $db->query( $sql );
    $rec = $db->fetchassoc();
    $log->log( "tipologia_cfu - classe: " . json_encode( $rec ) );

    /*
    echo $sql;
    echo "<br>";
    print_r( $rec );
    */


    $ar = [];

    $sql = sprintf( "SELECT DISTINCT sta.id as id, sta.nome_tipologia as text
      FROM ssm.ssm_tipologie_attivita sta
      LEFT JOIN ssm.ssm_scuole_pds_obiettivi so
        ON JSON_CONTAINS(so.idtipologie_attivita, %s )
      WHERE idambito ='%s'
        AND idscuola_specializzazione='%s'
        AND so.idstatus=1",
      "concat('\"', sta.id, '\"')",
      $db->real_escape_string( $args['idambito'] ),
      $db->real_escape_string( $args['idscuola_specializzazione'] ) );
    $db->query( $sql );
    while( $recAttivita = $db->fetchassoc() ) {
      $ar[] = $recAttivita;
    }

    $log->log( "tipologia_cfu - attivita: " . json_encode( $recAttivita ) );

    $log->log( $sql );
    $log->log( json_encode( $ar ) );
    /*
    echo "<br>ATTIVITA: " . $sql;
    print_r( $ar );
    */



    $response->getBody()->write( json_encode( $ar ) );
    return $response;
  });

});


function settori_scientifici_list( $idscuola, $idambito = "" ) {
  $db = new dataBase();

  // estrae la classe della scuola
  $sql = sprintf( "SELECT ss.idpds_classe
    FROM ssm.ssm_scuole_atenei sa
    LEFT JOIN ssm.ssm_scuole ss ON ss.id=sa.idscuola
    WHERE sa.idstatus=1 AND ss.idstatus=1
      AND sa.id='%s'", $db->real_escape_string( $idscuola ) );
  $db->query( $sql );
  $rec = $db->fetchassoc();

  $whereAmbito = "";
  if( $idambito != "" )
    $whereAmbito = sprintf( "AND sad.id='%s'", $db->real_escape_string( $idambito ) );

  $sql = sprintf( "SELECT ss.id, ss.nome AS text
    FROM
    ssm.ssm_pds_ambiti_disciplinari sad
    LEFT JOIN ssm.ssm_settori_scientifici ss ON JSON_CONTAINS(
      sad.idsettori_scientifici, %s )
    LEFT JOIN ssm.ssm_scuole_pds_obiettivi spo ON spo.idambito=sad.id
  WHERE sad.idclasse='%s'
    AND sad.idstatus=1
    AND ss.idstatus = 1
    AND spo.idstatus=1
    AND spo.idscuola_specializzazione='%s'
    %s
  ORDER BY text",
    "concat('\"', ss.id, '\"')",
    $db->real_escape_string( $rec['idpds_classe'] ),
    $db->real_escape_string( $idscuola ),
    $whereAmbito
  );
  $db->query( $sql );
  while( $rec = $db->fetchassoc() ) {
    $arSS[] = $rec;
  }

  return $arSS;

}

function getAnniScuola($idScuola) {
  $utils = new Utils();

  $arSql = array(
    "select" => ["numero_anni"],
    "from" => "ssm.ssm_scuole s",
    "join" => [
      [
        "ssm.ssm_scuole_atenei sa",
        " sa.idscuola=s.id"
      ]
    ],
    "where" => [
      [
        "field" => "sa.id",
        "value" => $idScuola
      ]
    ]
  );

  $arrSql = $utils->dbSelect($arSql);
  $anniScuola = $arrSql['data'][0]['numero_anni'];

  $ar = array();
  for ($i=1; $i <= $anniScuola; $i++) {
    $ar[] = array("id" => "$i", "text" => $i . "° anno");
  }
  return $ar;
}


?>
