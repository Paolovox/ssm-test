<?php


session_start();

define( "STANDBY", false );
define( "STANDBY_MESSAGE", "Il sistema è in aggiornamento." );

require 'vendor/autoload.php';
require 'CasSoap.php';
use \ottimis\phplibs\dataBase;

if (isset($_SESSION['db']))  {
  putenv("DB_NAME=" . $_SESSION['db']);
} else {
  $url = $_SERVER['HTTP_ORIGIN'];
  $db = new dataBase();

  $sql = sprintf("SELECT db_name FROM ssm.ssm_atenei WHERE JSON_CONTAINS(urls, '\"%s\"')", $url);
  $db->query($sql);
  $row = $db->fetchassoc();
  if (isset($row['db_name']))  {
    $_SESSION['db'] = $row['db_name'];
    putenv("DB_NAME=" . $row['db_name']);
  }
}


// Slim framework
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy as RouteCollectorProxy;
use Slim\Exception\HttpNotFoundException as HttpNotFoundException;
use Slim\Factory\AppFactory;

use \ottimis\phplibs\Logger;
use \ottimis\phplibs\Utils;
use \ottimis\phplibs\Auth;
use \ottimis\phplibs\UUID;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


error_reporting(1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$app = AppFactory::create();

/*** ERROR HANDLER */
// Add Error Handling Middleware
$displayErrorDetails = true;
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, false, false);
$errorMiddleware->setDefaultErrorHandler($errorHandler);
/** FINE ERROR HANDLER */


//putenv("DB_HOST=mysql.mysql.svc.cluster.local");

$ips = array(
  '93.48.240.185',
  '10.4.99.182',
  '10.4.98.182',
  '10.4.94.90',
  '10.4.95.90',
  '194.79.195.222'
);
// Logger::api($app, $ips);
Logger::api($app);

$auth = new Auth("TOKENOTTIMIS", "func", "idRole", "scopes", "extra");


$authMW = function (Request $request, RequestHandler $handler) use ($auth) {
  $authHeaders = $request->getHeader('Authorization');
  if (sizeof($authHeaders) == 0)  {
      $response = new Slim\Psr7\Response();
      $response->getBody()->write('Utente non autenticato');
      return $response
              ->withStatus(401);
  }
  $token = substr($authHeaders[0], 7);

  $ret = verifyToken( $token, $auth );
  if ($ret['success']) {
      $request = $request->withAttribute('user', $ret['user'] );
      $response = $handler->handle($request);
      return $response;
  } else {
      $response = new GuzzleHttp\Psr7\Response();
      $response->getBody()->write($ret['errorDescription']);
      return $response
        ->withStatus(401);
  }
};


$app->add(function (Request $request, RequestHandler $handler) {
  $uri = $request->getUri();
  $path = $uri->getPath();

  if ($path != '/' && substr($path, -1) == '/') {
      // recursively remove slashes when its more than 1 slash
      while (substr($path, -1) == '/') {
          $path = substr($path, 0, -1);
      }

      // permanently redirect paths with a trailing slash
      // to their non-trailing counterpart
      $uri = $uri->withPath($path);

      if ($request->getMethod() == 'GET') {
          $response = new \Slim\Psr7\Response();
          return $response
              ->withHeader('Location', (string) $uri)
              ->withStatus(301);
      } else {
          $request = $request->withUri($uri);
      }
  }

  return $handler->handle($request);
});


// INFO: Includes all files in includes folder
require_once( "CRUD.php" );
foreach (glob("includes/*.php") as $filename) {
  require $filename;
}


define( "APP_BACKEND", 1 );
define( "APP_FRONTEND", 2 );


$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($request, $handler) {

    $response = $handler->handle($request);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});



$app->add(function ($request, $handler) {

  if( STANDBY && $request->getMethod() != 'OPTIONS' ) {
    $uri = $request->getUri();
    $path = $uri->getPath();

    if( $path != "/standby" ) {
      $response = new Slim\Psr7\Response();
      $response->getBody()->write('Sistema in aggiornamento');
      return $response
      ->withHeader('Access-Control-Allow-Origin', '*')
      ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
      ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
      ->withStatus(418);
    }
  }

  $response = $handler->handle($request);
  return $response
          ->withHeader('Access-Control-Allow-Origin', '*')
          ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
          ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});


$app->get('/logincatttest', function (Request $request, Response $response, $args) {
    putenv("DB_NAME=ssm_unicatt");

    $user = array(
    "codice_fiscale" => "MSSMSM64H02D077X",
    "nome" => $_SERVER['HTTP_UNICATT_NAME'],
    "cognome" => $_SERVER['HTTP_UNICATT_SURNAME'],
    "email" => $_SERVER['HTTP_EMAIL']
  );
    $token = loginUnicatt($user);
    $response->getBody()->write($token);
    return $response->withStatus(200);
});
$app->get('/', function (Request $request, Response $response, $args) {
  return $response->withStatus(404);
});


$app->get('/standby', function (Request $request, Response $response, $args) {
  $response->getBody()->write( json_encode( array( "message" => STANDBY_MESSAGE ) ) );
  return $response
    ->withStatus(200)
    ->withHeader('Content-Type', 'application/json');
});

$app->get('/logincatt', function (Request $request, Response $response, $args) {
  putenv("DB_NAME=ssm_unicatt");

  $log = new Logger();
  $log->log('SSO CATTOLICA -> ' . json_encode($_SERVER));

  $user = array(
    "codice_fiscale" => $_SERVER['HTTP_UNICATT_CODICEFISCALE'],
    "nome" => $_SERVER['HTTP_UNICATT_NAME'],
    "cognome" => $_SERVER['HTTP_UNICATT_SURNAME'],
    // "tipologia" => $_SERVER['HTTP_TIPOLOGIA'],
    "email" => $_SERVER['HTTP_EMAIL'],
    // "ext_id" => $_SERVER['HTTP_USERID'],
  );
  $token = loginUnicatt($user);
  // $response->getBody()->write(json_encode($_COOKIE));
  return $response->withHeader('Location', "/login?token=$token")->withStatus(302);
});

$app->get('/ala', function (Request $request, Response $response, $args) {
    $response->getBody()->write("ciao");
    return $response;
});

$app->get('/excel', function (Request $request, Response $response, $args) {
  $inputFileName = 'dataset/20200226_EXPORT_DB_FRONTIER_ANOMOS/20200226_SCUOLA.xlsx';

  try {
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
  } catch( Exception $e ) {
    echo $e->getMessages();
  }
  $response->getBody()->write('Ciao 3');
    return $response;
});



$app->get('/download/{id}/{token}', function(Request $request, Response $response, $args) use ($auth) {
  $log = new Logger();
  $Utils = new Utils();
  $db = new dataBase();

  $user = verifyToken($args['token'], $auth);
  $user = $user['user'];

  if( $user['id'] == "" ) {
    $response->getBody()->write("Utente non riconosciuto 1");
    return $response
      ->withStatus(400)
      ->withHeader('Content-Type', 'text/plain');
  }

  if ($user['idruolo'] != 8)  {
    $sql = sprintf( "SELECT idutente FROM ssm_registrazioni WHERE JSON_CONTAINS(JSON_EXTRACT(attach, '$[*].id'), '[%d]')", $db->real_escape_string( $args['id'] ) );
    $db->query( $sql );
    $idutente = $db->fetchassoc()['idutente'];
    $sql = sprintf("SELECT idateneo FROM ssm_utenti_ruoli_lista WHERE idutente='%s'", $db->real_escape_string($idutente));
    $db->query($sql);
    $idateneo = $db->fetchassoc()['idateneo'];
  }

  $sql = sprintf( "SELECT * FROM attach WHERE id='%d'", $db->real_escape_string( $args['id'] ) );
  $db->query( $sql );
  $rec = $db->fetchassoc();
  $file = sprintf( "%s/%s/%s/%s", ATTACH_BASE_PATH, $user['idateneo'] ? $user['idateneo'] : $idateneo, $rec['path'], $rec['file_name'] );

  //  readfile($file);
  $f = fopen( $file, 'rb' );
  $stream = new \Slim\Psr7\Stream( $f );

//  $response->getBody()->write( $f );

  return $response
    ->withHeader('Content-Type', 'application/octet-stream')
    ->withHeader('Content-Transfer-Encoding', 'binary')
    ->withHeader('Content-Disposition', 'inline;filename="'. $rec['original_file_name'].'"')
    ->withHeader('Expires', '0')
    ->withHeader('Cache-Control', 'must-revalidate')
    ->withHeader('Pragma', 'public')
    ->withHeader('Content-Length', filesize($file) )
    ->withBody( $stream );

});




$app->get('/coorte_duplica/{idcoorte}', function (Request $request, Response $response, $args) {

//  coorte_duplica( "0992347a-8ad1-4975-8ed6-a5481c853d2d" );
  $ret = coorte_duplica( $args['idcoorte'] );

  if( $ret['success'] == 1 ) {
      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'text/plain');
  } else {
      $response->getBody()->write( json_encode( $ret['error'] ) );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
  }

})->add( $authMW );








$app->group('/attivita', function (RouteCollectorProxy $group) use ($auth) {

  $data = [
    "table" => "Attivita",
    "id" => "id",
    "sort" => "desc_attivita",
    "order" => "asc",
  ];
  $crud = new CRUD( $data );


  // list
  $group->get('', function (Request $request, Response $response) use ($auth, $crud) {
      $p = $request->getQueryParams();

      $p['_id_sds'] = $p['id_sds'];
      //$p['_idstatus'] = "1";

      $res = $crud->record_list( $p );
      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $group->get('/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $res = $crud->record_get( $args['id'] );
      $response->getBody()->write( json_encode( $res['data'][0] ) );
      return $response;
  });

  // new
  $group->put('', function (Request $request, Response $response) use ($crud ) {
    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "attivita", $p );
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
  $group->post('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "attivita", $p );
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
  $group->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
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

});





/* Che cos'era? FIXME:

$app->group('/settori_scientifici', function (RouteCollectorProxy $group) use ($auth, $args) {

  $data = [
    "table" => "Settori_scientifici",
    "table_join" => "Settori_scientifici ss",
    "id" => "id",
    "sort" => "desc_settore_scientifico",
    "order" => "asc",
    "fields_list" => "",
    "list_fields" => [ "ss.id", "ss.desc_settore_scientifico", "if(sett_obblig='S','Sì','No') as sett_obblig", "aprof.desc_att_professionalizzante" ],
    "list_join" => [
      [
          "Att_professionalizzante aprof",
          " aprof.id=ss.settore_tipo_sett "
      ]
    ]

  ];
  $crud = new CRUD( $data );

  // list
  $group->get('', function (Request $request, Response $response) use ($auth, $crud, $args) {

      $p = $request->getQueryParams();
      $p['_id_sds'] = $p['id_sds'];
      $res = $crud->record_list( $p );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $group->get('/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $Utils = new Utils();

      $res = $crud->record_get( $args['id'] )['data'][0];
      $res['sett_obblig'] = $res['sett_obblig'] == 'S' ? true:false;

      $ar = array( "table" => "Att_professionalizzante", "value" => "id", "text" => "desc_att_professionalizzante", "order" => "id" );
      $res['att_professionalizzante_list'] = $Utils->_combo_list( $ar, true, "" );

      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // new
  $group->put('', function (Request $request, Response $response) use ($crud ) {
    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "settori_scientifici", $p );
    if( $retValidate != "" ) {
      $response->getBody()->write( $retValidate );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $res = $crud->record_new( $p );
    $res['sett_obblig'] = $res['sett_obblig'] == 'S' ? true:false;

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
  $group->post('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);
    $p['sett_obblig'] = $p['sett_obblig'] == "true" ? "S":"N";

    $retValidate = validate( "settori_scientifici", $p );
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
  $group->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
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

});

*/






$app->group('/sedi_scuola', function (RouteCollectorProxy $group) use ($auth) {

  $data = [
    "table" => "Sedi_scuola",
    "id" => "id",
    "sort" => "nome_sede_scuola",
    "order" => "asc",
    "status_field" => "idstatus",
    "update_field" => "date_update",
    "create_field" => "date_create"
  ];
  $crud = new CRUD( $data );


  // list
  $group->get('', function (Request $request, Response $response) use ($auth, $crud) {
      $p = $request->getQueryParams();
      $p['_idstatus'] = "1";
      $res = $crud->record_list( $p );
      $response->getBody()->write( json_encode( $res ) );
      return $response;
  });

  // get
  $group->get('/{id}', function (Request $request, Response $response, $args) use ($crud) {
      $res = $crud->record_get( $args['id'] );
      $response->getBody()->write( json_encode( $res['data'][0] ) );
      return $response;
  });

  // new
  $group->put('', function (Request $request, Response $response) use ($crud ) {
    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "sedi_scuola", $p );
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
  $group->post('/{id}', function (Request $request, Response $response, $args) use ($crud) {
    $log = new Logger();

    $p = json_decode($request->getBody(), true);

    $retValidate = validate( "sedi_scuola", $p );
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
  $group->delete('/{id}', function (Request $request, Response $response, $args) use ($crud) {
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

});







// Users
$app->group('/user', function (RouteCollectorProxy $group) use ($auth) {

  $group->post('/login', function (Request $request, Response $response) use ($auth) {
      $p = json_decode($request->getBody(), true);
      $log = new Logger();

      $log->log(getenv("DB_NAME"));

      $tokenVerify = false;
      if(isset($p['token']))  {
        $ret = verifyToken($p['token'], $auth);
        //$log->log(json_encode($ret));
        if ($ret['success'] && $ret['user']) {
          $p['email'] = $ret['user']['email'];
          $tokenVerify = true;
        } else {
          $response = new GuzzleHttp\Psr7\Response();
          $response->getBody()->write($ret['errorDescription']);
          return $response
            ->withStatus(401);
        }
      }

      if ($p['email'] == '')  {
        $response->getBody()->write("Si è verificato un problema, riprova.");
          return $response
            ->withStatus(401);
      }

      $db = new dataBase();
      $sql = sprintf("SELECT id,cognome, nome, email, password, ruoli
        FROM ssm_utenti
        WHERE email='%s' AND idstatus = 1", $db->real_escape_string($p['email']));
      //$log->log($sql);
      $db->query($sql);
      $rec = $db->fetchassoc();

      if (($rec['id'] != "" && password_verify($p['password'], $rec['password']) === true) || $tokenVerify) {
          unset($rec['password']);

          $recUtente = utente_data_get($rec['id'], $p['idruolo']);
          if (!$recUtente)  {
            $response->getBody()->write("Utente non riconosciuto");
            return $response
              ->withStatus(400)
              ->withHeader('Content-Type', 'text/plain');
          }
          //$log->log(json_encode($recUtente));

          if( $recUtente[0]['idruolo'] == $recUtente[1]['idruolo'] ) {
            $recUtente = $recUtente[0];
          } elseif( sizeof( $recUtente ) > 1 ) { // se l'utente ha più ruoli ritorna la lista dei ruoli
            $roles['roles'] = utente_roles_list( $rec['id'] );
            $response->getBody()->write( json_encode( $roles ) );
            return $response
              ->withStatus(200)
              ->withHeader('Content-Type', 'application/json');
          } else {
            $recUtente = $recUtente[0];
          }

          $rec['idateneo'] = $recUtente['idateneo'];
          $rec['idscuola'] = $recUtente['idscuola'];
          $rec['idruolo'] = $recUtente['idruolo'];
          $rec['anno_scuola'] = $recUtente['anno_scuola'];

          $jwt = $auth->tokenRefresh(1, $rec);
          $rec['token'] = $jwt;

          foreach ($recUtente as $k => $v) {
              $rec[$k] = $v;
          }

          $response->getBody()->write(json_encode($rec));
          return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json');
      } else {
        $response->getBody()->write("Utente non riconosciuto");
        return $response
          ->withStatus(400)
          ->withHeader('Content-Type', 'text/plain');
      }
  });
  /*
    $sql = sprintf( "SELECT id,cognome_utente,nome_utente,mail_utente,psw_hash
      FROM Utenti
      WHERE mail_utente='%s'", $db->real_escape_string( $p['email'] ) );
    $db->query( $sql );
    $rec = $db->fetchassoc();

    if( $rec['id'] > 0 && password_verify( $p['password'], $rec['psw_hash'] ) === true ) {
      unset( $rec['psw_hash'] );

      $jwt = $auth->tokenRefresh( 1, $rec );
      $rec['token'] = $jwt;

      $response->getBody()->write( json_encode( $rec ) );
      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
    } else {
      $response->getBody()->write( "Utente non riconosciuto" . $sql );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    */


  $group->post('/login/cas/{token}', function (Request $request, Response $response, $args) use ($auth) {
    $db = new dataBase();

    $a = new CASLogin();
    $res = $a->validateServiceTicket($args['token']);
    if( !$res['success'] ) {
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
    }

    $sql = sprintf("SELECT id,cognome, nome, email, password
      FROM ssm_utenti
      WHERE email='gb@ottimis.com'" );
    $db->query($sql);
    $rec = $db->fetchassoc();

    $recUtente = utente_data_get($rec['id']);
    $rec['idscuola'] = $recUtente['idscuola'];
    $rec['idruolo'] = $recUtente['idruolo'];
    $rec['anno_scuola'] = $recUtente['anno_scuola'];

    $jwt = $auth->tokenRefresh(1, $rec);
    $rec['token'] = $jwt;

    foreach ($recUtente as $k => $v) {
        $rec[$k] = $v;
    }

    $response->getBody()->write(json_encode($rec));
      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');

  });

  $group->get('/pass', function (Request $request, Response $response) use ($auth) {
//    $p = json_decode($request->getBody(), true);
    $p = $request->getQueryParams();

    print_r( $p );

    $psw = password_hash( $p['password'], PASSWORD_BCRYPT );


    $db = new dataBase();
    $sql = sprintf( "UPDATE Utenti SET psw_hash='%s' WHERE id='%s'", $db->real_escape_string( $psw ), $db->real_escape_string( $p['id'] ) );
    $db->query( $sql );
    $rec = $db->fetchassoc();

    echo $sql;

    $response->getBody()->write( json_encode( $rec ) );
    return $response
      ->withStatus(200)
      ->withHeader('Content-Type', 'application/json');


  });


});




$app->get('/combo_prestazioni_update', function (Request $request, Response $response, $args) use ($crud ) {
  $db = new dataBase();
  $Utils = new Utils();
  $uuid = new UUID();
  echo "OK";

  $sql = sprintf( "SELECT sa.id, combo_ok.idscuola, combo_ok.nome
    FROM ssm.ssm_scuole_atenei sa
    LEFT JOIN (
      SELECT DISTINCT idscuola,ssm.ssm_registrazioni_combo.nome
      FROM ssm.ssm_registrazioni_combo
      WHERE idtipo=1
    ) combo_ok ON sa.id = combo_ok.idscuola" );
  $db->query( $sql );
  while( $rec = $db->fetchassoc() ) {
    if( $rec['idscuola'] == "" ) {
      $ar = array(
        "id" => $uuid->v4(),
        "idscuola" => $rec['id'],
        "nome" => "Prestazioni",
        "idtipo" => 1,
        "idstatus" => 1,
        "date_create" => "now()",
        "date_update" => "now()"
      );

      $ret = $Utils->dbSql( true, "ssm.ssm_registrazioni_combo", $ar, "", "" );
      if( $ret['success'] != 1 ) {
        print_r( $ret );
        break;
      }

    }
//    print_r( $rec );
  }

});



$app->get('/survey/report/{idsurvey}', function (Request $request, Response $response, $args) use ($auth, $crud) {
  $db = new dataBase();
  $log = new Logger();

  $sql = sprintf( "SELECT sd.id as iddomanda,
      domanda, risposte, idtipo_risposta, titolo
      FROM ssm_survey_domande sd
      LEFT JOIN ssm_survey s ON s.id=sd.idsurvey
      WHERE sd.idsurvey='%s'
        AND sd.idstatus=1 AND s.idstatus=1", $db->real_escape_string( $args['idsurvey'] ) );
  $db->query( $sql );
  while( $rec = $db->fetchassoc() ) {
    $dd[$rec['iddomanda']] = $rec['domanda'];

    switch( $rec['idtipo_risposta'] ) {
      case 2:
      case 3:
        $r = json_decode( $rec['risposte'], true );
        foreach( $r as $k ) {
          $domande[$rec['iddomanda']][$k] = 0;
        }
        break;
      case 4:
        $domande[$rec['iddomanda']]['No'] = 0;
        $domande[$rec['iddomanda']]['Si'] = 0;
        break;
    }
  }


  $sql = sprintf( "SELECT sr.*, sd.idtipo_risposta
      FROM ssm_survey_risposte sr
      LEFT JOIN ssm_survey_domande sd ON sd.id=sr.iddomanda
      LEFT JOIN ssm_survey s ON s.id=sd.idsurvey
      WHERE sr.idsurvey='%s'", $db->real_escape_string( $args['idsurvey'] ) );
  $db->query( $sql );
  while( $rec = $db->fetchassoc() ) {


    //print_r( $rec );
    switch( $rec['idtipo_risposta'] ) {
      case 2:
        $r = json_decode( $rec['risposta'], true );
        if( is_array( $r ) ) {
          foreach( $r as $k ) {
            $domande[$rec['iddomanda']][$k]++;
            $domande[$rec['iddomanda']]['Totale']++;
          }
        } else {
          $risposta = str_replace('"','',$rec['risposta']);
          $domande[$rec['iddomanda']][$risposta]++;
          $domande[$rec['iddomanda']]['Totale']++;
        }
        break;

      case 3:
        $r = json_decode( $rec['risposta'], true );
        foreach( $r as $k ) {
          $domande[$rec['iddomanda']][$k]++;
          $domande[$rec['iddomanda']]['Totale']++;
        }
        break;
      case 4:
        if( $rec['risposta'] == "true" )
          $domande[$rec['iddomanda']]['Si']++;
        if( $rec['risposta'] == "false" )
          $domande[$rec['iddomanda']]['No']++;
        $domande[$rec['iddomanda']]['Totale']++;

        break;
    }
  }


  foreach( $domande as $k => $v ) {
    $t = $v['Totale'];
    unset( $v['Totale'] );
    foreach( $v as $a => $b ) {
      $res[] = array( "domanda" => $k,
        "domanda_text" => $dd[$k],
        "risposta" => $a,
        "num" => $b,
        "perc" => $t > 0 ? number_format( ($b/$t)*100, 2, ",", "" ) : ""
      );
    }

    $ar[] = array(
      "domanda" => $res[0]['domanda_text'],
      "risposte" => $res
    );
    $res = array();
  }


  /*
  print_r( $ar );
  print_r( $res );
  print_r( $domande );
  */

  $response->getBody()->write( json_encode( $ar ) );
    return $response
      ->withStatus(200)
      ->withHeader('Content-Type', 'application/json');


 });



$app->map(['GET', 'POST', 'DELETE', 'PUT' ], '/{routes:.+}', function ($request, $response) {
    throw new HttpNotFoundException($request);
});

try {
    $app->run();
} catch (\Exception $e) {
    echo $e->getMessages();
}


function verifyToken($token, $auth) {
  $log = new Logger();

  // verificare token sul db

  $ret['user'] = $auth->tokenDecode( $token );
  //$log->log( "verifyToken: " . json_encode( $ret ) );

  $ret['success'] = 1;
  return $ret;


}


function responseSet( $response, $res ) {
  if( $res['success'] == 1 ) {
    $response->getBody()->write( json_encode( $res ) );
      return $response
        ->withStatus(200)
        ->withHeader('Content-Type', 'application/json');
  } else {
      $response->getBody()->write( "Utente non riconosciuto" . $sql );
      return $response
        ->withStatus(400)
        ->withHeader('Content-Type', 'text/plain');
  }

}

function _form_check( $parameters, $arCheck ) {
  $log = new Logger();

  /*
  $log->log( "parametri: " . json_encode( $parameters ) );
  $log->log( "arCheck: " . json_encode( $arCheck ) );
  */

	foreach( $arCheck as $k => $v ) {
    //$log->log( "verifica " . json_encode( $v ) );

    $err = "";
    if( $v[1] === true && $parameters[$v[0]] == "" ) {
      $err = $v[4];
      return $err;
    }

    if( $v[2] > 0 && strlen( $parameters[$v[0]]) < $v[2] ) {
      $err = $v[4];
      return $err;
    }

    if( $v[3] > 0 && (is_numeric( $parameters[$v[0]] ) == false || $parameters[$v[0]] < $v[3] ) ) {
      $err = $v[4];
      return $err;
    }
	}
	return "";
}



function validate( $key, $p ) {
  $arCheck['atenei'] = [
    ["nome_ateneo",       true, 6, 0, "Nome ateneo errato o mancante." ],
    ["url_public_at",     true, 6, 0, "URL ateneo errato o mancante (minimo 6 caratteri)." ],
    ["gg_alert_doc_at",   true, 0, 1, "Indicare giorni alert per cassetto documentale." ],
    ["mail_ateneo",       true, 10, 0, "Mail ateneo errata o mancante." ],
    ['indirizzo_ateneo',  true, 5, 0, "Indirizzo ateneo errato o mancante."],
    ['comune_ateneo',     true, 5, 0, "Comune ateneo errato o mancante."],
    ['cap_ateneo',        true, 5, 0, "CAP ateneo errato o mancante."],
    ['nonce_ateneo',      true, 0, 1, "Nonce errato o mancante."],
    ['sso',               false, 1, 0, "SSO errato o mancante."],
    ['campo_sso',         false, 1, 0, "Campo SSO errato o mancante."],
  ];

  $arCheck['scuole'] = [
    ["nome_scuola",       true, 6, 0, "Nome errato o mancante." ],
  ];

  $arCheck['sedi_scuola'] = [
    ["nome_sede_scuola",       true, 6, 0, "Sede scuola errato o mancante." ],
  ];

  $arCheck['utenti'] = [
    ["email",         true, 6, 0, "Email errata o mancante." ],
    ["cognome",       true, 2, 0, "Cognome errato o mancante (minimo 2 caratteri)." ],
    ['nome',          true, 2, 0, "Nome errato o mancante."],
    // ['password',      true, 6, 0, "Password errata o mancante."],
  ];

  $arCheck['settori_scientifici'] = [
    ["nome",  true, 3, 0, "Settore scientifico errato o mancante." ],
  ];

  $arCheck['ambiti_disciplinari'] = [
    ["nome_ambito_disciplinare",  true, 3, 0, "Ambito disciplinare errato o mancante." ],
  ];

  $arCheck['aziende'] = [
    ["nome",  true, 3, 0, "Nome azienda errato o mancante." ],
  ];

  $arCheck['presidi'] = [
    ["nome",  true, 3, 0, "Nome presidio errato o mancante." ],
  ];

  $arCheck['registrazioni_attivita'] = [
    ["nome", true, 3, 0, "Nome attività errato o mancante." ],
  ];

  $arCheck['turni'] = [
    ["data_inizio", true, 8, 0, "Data inizio errata o mancante." ],
    ["data_fine", true, 8, 0, "Data inizio errata o mancante." ],
    ["idspecializzando", true, 1, 0, "Specificare uno Specializzando." ],
    ["idtutor", true, 1, 0, "Specificare un Tutor." ],
    // ["idunita", true, 1, 0, "Indicare un'unità operativa." ],
  ];

  $retCheck = _form_check( $p, $arCheck[$key] );
  if( $retCheck != "" ) {
    return $retCheck;
  }


}
