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

define( "ATENEO_UNIMI", "65edb001-b9be-4da9-ae28-2e1b96f53be8" );
define( "UNIMI_USER_DIRETTORE_SCUOLA", 2 );
define( "UNIMI_USER_DIRETTORE_UO", 6 );
define( "UNIMI_USER_TUTOR", 5 );

function import_unimi_studenti() {
  $db = new dataBase();
  $db2 = new dataBase();
  $Utils = new Utils();

  echo "<--- OK 3 ---> ";

  $base = array(
      "id" => "a621bcc9-dd77-4fa6-91f4-3c88acd4fa0b",
      "idateneo" => "65edb001-b9be-4da9-ae28-2e1b96f53be8",
      "idscuola" => "a621bcc9-dd77-4fa6-91f4-3c88acd4fa0b"
  );

  $sql = sprintf("SELECT *
    FROM ssm_unimi_import_scuole.SPECIALIZZANDO ut_in
    LEFT JOIN ssm_utenti ut ON ut.id=concat('UNIMI-S-',ut_in.CODICE_FISCALE)
    WHERE FLAG_STUDENTE=0 AND ATTIVO=1");
  $db->query($sql);
  echo $sql;
  echo $db->error();

  while( $rec = $db->fetchassoc() ) {
      echo "<br>" . $rec['COGNOME'] . " - " . $rec['COORTE'] . " - " . $rec['ANNO_CORSO'];

      $ins = array(
          "id" => "UNIMI-SP-" . $rec['CODICE_FISCALE'],
          "nome" => $rec['NOME'],
          "cognome" => $rec['COGNOME'],
          "genere" => $rec['SESSO'],
          "matricola" => $rec['MATRICOLA'],
          "email" => $rec['EMAIL'],
          "codice_fiscale" => $rec['CODICE_FISCALE'],
          "residenza_indirizzo" => $rec['INDIRIZZO_RESIDENZA'],
          "residenza_citta" => $rec['LUOGO_RESIDENZA'],
          "telefono" => $rec['TELEFONO'],
          "data_nascita" => substr($rec['DATA_NASCITA'], 0, 10),
          "luogo_nascita" => $rec['LUOGO_NASCITA'],
          "residenza_cap" => $rec['CAP_RESIDENZA'],
          "anno_scuola" => $rec['ANNO_CORSO'],
          "idstatus" => 1,
          "date_create" => "now()",
          "date_update" => "now()",
          "password" => "NOAUTH_UNIMI"
      );

      $cod_scuola = $rec['COD_SCUOLA'];
      $idscuola = idscuola_get( $db2, $cod_scuola, ATENEO_UNIMI );

      print_r( $ins );

      //$coorte = sprintf( "%s/%s", $rec['idcoorte'], $rec['idcoorte'] + 1 );
      $coorti_ok = array( '2016/2017', '2017/2018', '2018/2019', '2019/2020' );
      if( !in_array( $rec['COORTE'], $coorti_ok ) ) {
        echo "<b>scarta</b>";
        continue;
      }

      $idcoorte = id_coorte_get( $db2, $idscuola['id'], $rec['COORTE'] );

      if( $idcoorte == "" ) {
        $notfound[$cod_scuola] = $idscuola['nome'] . ' - ' . $coorte;
        echo "<br><b>Coorte non trovata</b> - idscuola: " . $idscuola['id'] . " - " . $rec['idcoorte'];
      } else {
        $ins['idcoorte'] = $idcoorte;
      }


      $ret = $Utils->dbSql(true, "ssm.ssm_utenti", $ins, "", "");
      print_r($ret);
      if ($ret['success'] != 1) {
          $db->error();
          print_r($ret);
          exit;
      }

      echo "* OK 3 *";
      //exit;

      //print_r( $ins );
      // elimina i ruoli dello specializzando
      $sqlDelete = sprintf( "DELETE FROM ssm.ssm_utenti_ruoli_lista WHERE idutente='%s'",
        $ins['id'] );
      $db2->query( $sqlDelete );

      $ins_ruolo = array(
          "idutente" => $ins['id'],
          "idruolo" => USER_SPECIALIZZANDO,
          "idateneo" => ATENEO_UNIMI,
          "idscuola" => $idscuola['id'],
          "idstatus" => STATUS_OK,
          "date_create" => "now()",
          "date_update" => "now()"
      );

      $ret = $Utils->dbSql(true, "ssm.ssm_utenti_ruoli_lista", $ins_ruolo, "", "");
      if ($ret['success'] != 1) {
          print_r($ret);
          exit;
      }
  }

  return true;


}

function import_unimi_tutor() {

  $db = new dataBase();
  $db2 = new dataBase();
  $Utils = new Utils();

  $sql = sprintf( "SELECT ar.*, an.*, sc.idscuola, sat.id as scuola_id
    FROM ssm_unimi_import_scuole.ANAGRAFICA_RUOLO ar
    LEFT JOIN ssm_unimi_import_scuole.ANAGRAFICA an ON an.COD_FISCALE=ar.COD_FISCALE
    LEFT JOIN ssm.ssm_utenti ut ON ut.id=concat('UNIMI-T-',ar.COD_FISCALE)
    LEFT JOIN ssm.ssm_associazioni_scuole sc ON sc.idscuola_ext=COD_SCUOLA
    LEFT JOIN ssm.ssm_scuole_atenei sat ON sat.idateneo='65edb001-b9be-4da9-ae28-2e1b96f53be8' AND sat.idscuola=sc.idscuola
    WHERE ID_RUOLO IN (2,6,5) ar.ATTIVO=1" );

  $db->query($sql);
  echo $sql;
  while ($rec = $db->fetchassoc()) {
      echo "<br>" . $rec['COGNOME'];

      $ins = array(
          "id" => "UNIMI-T-" . $rec['COD_FISCALE'],
          "nome" => $rec['NOME'],
          "cognome" => $rec['COGNOME'],
          "genere" => $rec['SESSO'],
          "matricola" => $rec['MATRICOLA'],
          "email" => trim($rec['MAIL'])=="" ? $rec['MAIL_ALTERNATIVA'] : $rec['MAIL'],
          "codice_fiscale" => $rec['COD_FISCALE'],
          "residenza_indirizzo" => $rec['INDIRIZZO_RESIDENZA'],
          "residenza_citta" => $rec['LUOGO_RESIDENZA'],
          "telefono" => $rec['TELEFONO'],
          "luogo_nascita" => $rec['LUOGO_NASCITA'],
          "residenza_cap" => $rec['CAP_RESIDENZA'],
          "idstatus" => 1,
          "date_create" => "now()",
          "date_update" => "now()",
          "password" => "NOAUTH_UNIMI"
      );

      if ($rec['DATA_NASCITA'] != "") {
          $ins['data_nascita'] = $rec['DATA_NASCITA'];
      }

      $ret = $Utils->dbSql(true, "ssm.ssm_utenti", $ins, "", "");
      //print_r($ret);
      if ($ret['success'] != 1) {
          $db->error();
          print_r($ret);
          exit;
      }

      echo "OK2";
      //print_r( $ins );


      // sistema ruoli
      switch( $rec['ID_RUOLO'] ) {
        case UNIMI_USER_TUTOR:
          $ssm_ruolo = 7;
          break;
        case UNIMI_USER_DIRETTORE_SCUOLA:
          $ssm_ruolo = 5;
          break;
        case UNIMI_USER_DIRETTORE_UO:
          $ssm_ruolo = 6;
          break;
      }


      $tutor = getReteTutorUnimi( $rec['COD_FISCALE'] );
      foreach ($tutor as $k => $v) {
          $ins_ruolo = array(
              "idutente" => $ins['id'],
              "idruolo" => $ssm_ruolo,
              "idateneo" => ATENEO_UNIMI,
              "idscuola" => $rec['scuola_id'],
              "idpresidio" => $v['idpresidio'],
              "idunita" => $v['idunita'],
              "idstatus" => STATUS_OK,
              "date_create" => "now()",
              "date_update" => "now()"
          );

          $sqlDelete = sprintf( "DELETE FROM ssm.ssm_utenti_ruoli_lista WHERE idutente='%s' AND idateneo='%s'",
            $ins_ruolo['idutente'],
            $ins_ruolo['idruolo'],
            $ins_ruolo['idateneo'] );
          $db2->query( $sqlDelete );

          print_r( $ins_ruolo );
          $ret = $Utils->dbSql(true, "ssm.ssm_utenti_ruoli_lista", $ins_ruolo, "", "", true);
          if ($ret['success'] != 1) {
            if( substr( $db->error(), 0, 4 ) != 1062 ) {
              print_r($ret);
              echo "<br>" . $db->error();
              exit;
            }
          }
      }

      //exit;

      //print_r( $ins_ruolo );
  }

  //echo $db->error();
}



function getReteTutorUnimi($codiceFiscale)  {
  $db = new Database();

  $sql = sprintf( "SELECT r.COD_PRESIDIO, p.idpresidio, u.idunita
    FROM ssm_unimi_import_scuole.ANAGRAFICA_RETE sc
    LEFT JOIN ssm_unimi_import_scuole.RETE r ON r.ID_RETE=sc.ID_RETE
    LEFT JOIN ssm.ssm_associazioni_presidi p ON p.idpresidio_ext=r.COD_PRESIDIO AND p.idazienda_ext=r.COD_AZIENDA
    LEFT JOIN ssm.ssm_associazioni_unita u ON u.idunita_ext=r.COD_UO AND u.idpresidio_ext=r.COD_PRESIDIO AND u.idazienda_ext=r.COD_AZIENDA
    WHERE COD_FISCALE='%s'", $db->real_escape_string( $codiceFiscale ) );
  $db->query( $sql );

  while ($rec = $db->fetchassoc()){
    $ar[] = $rec;
  }
  return $ar;
}


?>
