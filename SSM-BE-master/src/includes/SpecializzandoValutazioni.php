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

$app->group('/specializzando_valutazioni', function (RouteCollectorProxy $groupSpecvalutazioni) use ($auth) {

    $groupSpecvalutazioni->get('/specializzandi', function (Request $request, Response $response, $args) {

        $user = $request->getAttribute('user');
        $p = $request->getQueryParams();

        if ($user['idruolo'] == 7)  {
            // Tutor
            $arrSql = specializzandiListTutor($p, $user);
        } else {
            // Direttore o segr
            $arrSql = specializzandiListDirSegr($p, $user, $p['idScuola']);
        }

        if ( $user['idruolo'] >= 2 && $user['idruolo'] <= 4)    {
            $arrSql['scuole'] = scuoleList($user['idateneo']);
        }

        $response->getBody()->write(json_encode($arrSql, JSON_NUMERIC_CHECK));
        return $response->withHeader('Content-Type', 'application/json');
    });

    function specializzandiListTutor($p, $user)  {
        $Utils = new Utils();
        $log = new Logger();

        $p['count'] = $p['c'] != "" ? ($p['c']) : 20;
        $p['start'] = $p['p'] != "" ? ($p['p']-1) * $p['count'] : 0;
        $p['sort'] = $p['srt'] != "" ? $p['srt'] : 'specializzando_nome';
        $p['order'] = $p['o'] != "" ? $p['o'] : 'asc';

        $tutor = sprintf("(st.id is not null AND su.idstatus = 1)", $user['id']);
        $trainer = sprintf("r.idtutor = '%s' AND r.anno=su.anno_scuola AND st2.id is null AND su.idstatus = 1", $user['id']);

        if ($p['tt'] == 0) {
            $s = sprintf("(%s %s %s)    ", $p['tt'] != 1 ? $tutor : '', $p['tt'] == 0 ? 'OR' : '', $p['tt'] != 2 ? $trainer : '');
        } elseif ($p['tt'] == 1) {
            $s = $tutor;
        } else {
            $s = $trainer;
        }

        $arSql = array(
            "count" => true,
            "log" => true,
            "select" => [
                "DISTINCT su.id",
                "CONCAT(su.nome, ' ', su.cognome) as specializzando_nome",
                "IF(st.idtutor is not null, 'Si', '') as tipo_tutor",
                "IF(st.idtutor is not null, 1, 2) as idtipo_tutor",
                "su.anno_scuola",
                "url.idscuola",
                "sc.nome as nome_coorte",
                "CONCAT(su2.nome, ' ', su2.cognome) as tutor_in_turno",
            ],
            "from" => "ssm_utenti su",
            "join" => [
                [
                    "(
                        SELECT DISTINCT idtutor, idutente, data_registrazione, anno
                        FROM ssm_registrazioni
                        where idtutor ='" . $user['id'] . "' AND idstatus=1
                    ) r",
                    "r.idutente=su.id"
                ],
                [
                    "ssm_turni st",
                    "st.idspecializzando=su.id
                    AND st.idstatus=1 AND st.idtutor = '" . $user['id'] . "' AND st.anno=su.anno_scuola"
                ],
                [
                    "ssm_turni st3",
                    "st3.idspecializzando=su.id AND now() >= st3.data_inizio AND now() <= st3.data_fine AND st3.anno=su.anno_scuola AND st3.idstatus=1"
                ],
                [
                    "ssm_utenti su2",
                    "su2.id=st3.idtutor"
                ],
                [
                    "ssm.ssm_pds_coorti sc",
                    "sc.id=su.idcoorte"
                ],
                [
                    "ssm_turni st2",
                    "st.idspecializzando=su.id AND r.data_registrazione >= st.data_inizio
                    AND r.data_registrazione <= st.data_fine AND st2.anno=su.anno_scuola
                    AND st.idstatus=1 AND st2.idstatus=1 AND st.idtutor = '" . $user['id'] . "'"
                ],
                [
                    "ssm_utenti_ruoli_lista url",
                    "url.idutente=su.id"
                ]
            ],
            "where" => [
                [
                    "custom" => $s,
                ]
            ],
            "map" => function ($rec) use ($log, $user) {
                if ($rec['idtipo_tutor'] == 1) {
                    // $specializzando = specializzando_get($rec['id']);
                    $valutazioniCount = count(get_valutazioni_specializzando($rec['id'], false, $rec['anno_scuola'])['rows']);
                    $turniCount = count(checkTurniTutor($user['id'], $rec['id'], $rec['idscuola'], false, $rec['anno_scuola']));
                    // $log->log($valutazioniCount . " e " . $turniCount);
                    $regCount = registrazioniCountTutor($rec['id'], $user['id']);
                    $rec = array_merge($rec, $regCount);
                    if ($valutazioniCount < $turniCount) {
                        $rec['status_text'] = "Da valutare";
                        $rec['button_color'] = "#cc0000";
                        $rec['idstatus'] = 0;
                    } else {
                        $rec['status_text'] = "Valutato";
                        $rec['button_color'] = "#258e7a";
                        $rec['idstatus'] = 1;
                    }
                } else {
                    $rec['status_text'] = "Non in turno";
                    $rec['button_color'] = "#ffffff";
                }
                return $rec;
            },
            "order" => $p['sort'] != 'status_text' ? ($p['sort'] . " " . $p['order']) : "id asc",
            // "order" => $p['sort'] . " " . $p['order'],
            "limit" => [$p['start'], $p['count']]
        );

        if ($p['s'])    {
            $search = array(
                [
                    "field" => "(su.nome",
                    "operator" => " like ",
                    "value" => "%" . $p['s'] . "%",
                    "operatorAfter" => "OR"
                ],
                [
                    "field" => "su.cognome",
                    "operator" => " like ",
                    "value" => "%" . $p['s'] . "%",
                    "operatorAfter" => ") AND"
                ]
            );
            $where = $arSql['where'];
            $where = array_merge($search, $where);
            $arSql['where'] = $where;
        }

        $arrSql = $Utils->dbSelect($arSql);

        if ($p['sort'] == "status_text" && $p['order'] == "desc")   {
            usort($arrSql['rows'], function ($a, $b) {return $a['idstatus'] > $b['idstatus']; } );
        }
        if ($p['sort'] == "status_text" && $p['order'] == "asc")   {
            usort($arrSql['rows'], function ($a, $b) {return $a['idstatus'] < $b['idstatus']; } );
        }

        return $arrSql;
    }
    function specializzandiListDirSegr($p, $user, $idScuola = 0)  {
        $Utils = new Utils();
        $log = new Logger();

        $p['count'] = $p['c'] != "" ? ($p['c']) : 20;
        $p['start'] = $p['p'] != "" ? ($p['p']-1) * $p['count'] : 0;
        $p['sort'] = $p['srt'] != "" ? $p['srt'] : 'specializzando_nome';
        $p['order'] = $p['o'] != "" ? $p['o'] : 'asc';

        $sortDefault = true;
        if ($p['sort'] != 'status_text' &&
            $p['sort'] != 'registrate' &&
            $p['sort'] != 'inviate' &&
            $p['sort'] != 'confermate') {
            $sortDefault = false;
        }

        $arSql = array(
            "count" => true,
            "log" => true,
            "select" => [
                "DISTINCT su.id",
                "CONCAT(su.nome, ' ', su.cognome) as specializzando_nome",
                "su.anno_scuola",
                "url.idscuola",
                "sc.nome as nome_coorte",
                "CONCAT(su2.nome, ' ', su2.cognome) as tutor_in_turno",
                "IF(st.idtutor is not null, 'In turno', '') as tipo_tutor",
                "IF(st.idtutor is not null, 1, 2) as idtipo_tutor",
            ],
            "from" => "ssm_utenti su",
            "join" => [
                [
                    "ssm_turni st",
                    "st.idspecializzando=su.id AND st.idtutor='" . $user['id'] . "' AND st.anno=su.anno_scuola AND st.idstatus=1"
                ],
                [
                    "ssm_turni st2",
                    "st2.idspecializzando=su.id AND now() >= st2.data_inizio AND now() <= st2.data_fine AND st2.anno=su.anno_scuola AND st2.idstatus=1"
                ],
                [
                    "ssm_utenti su2",
                    "su2.id=st2.idtutor"
                ],
                [
                    "ssm_utenti_ruoli_lista url",
                    "url.idutente=su.id"
                ],
                [
                    "ssm.ssm_pds_coorti sc",
                    "sc.id=su.idcoorte"
                ]
            ],
            "where" => [
                [
                    "field" => "url.idscuola",
                    "value" => $user['idscuola'] ? $user['idscuola'] : $idScuola,
                    "operatorAfter" => "AND"
                ],
                [
                    "field" => "url.idruolo",
                    "value" => 8,
                    "operatorAfter" => "AND"
                ],
                [
                    "field" => "su.idstatus",
                    "value" => 1
                ]
            ],
            "map" => function ($rec) use ($log) {
                $valutazioniCount = count(get_valutazioni_specializzando($rec['id'], false, $rec['anno_scuola'])['rows']);
                $turniCount = count(checkTurniTutor(false, $rec['id'], $rec['idscuola'], true, $rec['anno_scuola']));
                $regCount = registrazioniCount($rec['id']);
                $rec = array_merge($rec, $regCount);
                // $log->log($valutazioniCount . " e " . $turniCount);
                if (($valutazioniCount < $turniCount) || ($valutazioniCount == 0)) {
                    $rec['status_text'] = "Da valutare";
                    $rec['button_color'] = "#cc0000";
                    $rec['idstatus'] = 0;
                } else {
                    $rec['status_text'] = "Valutato";
                    $rec['button_color'] = "#258e7a";
                    $rec['idstatus'] = 1;
                }
                return $rec;
            },
            "order" => $sortDefault ? "id asc" : ($p['sort'] . " " . $p['order']),
            "limit" => [$p['start'], $p['count']]
        );

       if ($p['s'])    {
            $search = array(
                [
                    "field" => "(su.nome",
                    "operator" => " like ",
                    "value" => "%" . $p['s'] . "%",
                    "operatorAfter" => "OR"
                ],
                [
                    "field" => "su.cognome",
                    "operator" => " like ",
                    "value" => "%" . $p['s'] . "%",
                    "operatorAfter" => ") AND"
                ]
            );
            $where = $arSql['where'];
            $where = array_merge($search, $where);
            $arSql['where'] = $where;
        }

        $arrSql = $Utils->dbSelect($arSql);

        if ($sortDefault) {
            if ($p['sort'] == 'status_text')    {
                $p['sort'] = "idstatus";
            }
            if ($p['order'] == "desc") {
                usort($arrSql['rows'], function ($a, $b) use ($p) {
                    return $a[$p['sort']] > $b[$p['sort']];
                });
            }
            if ($p['order'] == "asc") {
                usort($arrSql['rows'], function ($a, $b) use ($p) {
                    return $a[$p['sort']] < $b[$p['sort']];
                });
            }
        }

        return $arrSql;
    }

    function registrazioniCount($idSpecializzando)  {
        $db = new dataBase();
        $db->query(sprintf("SELECT count(*) as c from ssm_registrazioni WHERE idutente = '%s' AND idstatus=1 AND conferma_stato=0", $db->real_escape_string( $idSpecializzando )));
        $rec['registrate'] = $db->fetchassoc()['c'];
        $db->query(sprintf("SELECT count(*) as c from ssm_registrazioni WHERE idutente = '%s' AND idstatus=1 AND conferma_stato=1", $db->real_escape_string( $idSpecializzando )));
        $rec['inviate'] = $db->fetchassoc()['c'];
        $db->query(sprintf("SELECT count(*) as c from ssm_registrazioni WHERE idutente = '%s' AND idstatus=1 AND conferma_stato=2", $db->real_escape_string( $idSpecializzando )));
        $rec['confermate'] = $db->fetchassoc()['c'];
        return $rec;
    }

    function _registrazioniCountTutor( $idTutor, $idspecializzando, $stato, $trainerTutor = 2 )  {
        $db = new dataBase();
        $log = new Logger();

        $trainer = sprintf("SELECT
                    DISTINCT *, 1 as idtipo_tutor
                    FROM ssm_registrazioni
                    WHERE idtutor = '%s' AND idutente = '%s' AND idstatus = 1", $db->real_escape_string( $idTutor ), $db->real_escape_string( $idspecializzando ) );
        $tutor = sprintf( "( SELECT
                sr.*, 2 as idtipo_tutor
                FROM
                    ssm_registrazioni sr
                INNER JOIN
                    ssm_turni st ON (sr.idutente = st.idspecializzando
                    AND sr.data_registrazione >= st.data_inizio
                    AND sr.data_registrazione <= st.data_fine
                    AND st.idstatus=1)
                WHERE
                    st.idtutor = '%s' AND sr.idutente = '%s' AND sr.idstatus = 1)", $db->real_escape_string( $idTutor ), $db->real_escape_string( $idspecializzando ));
        switch ($trainerTutor) {
            case 0:
            $tt = "$trainer UNION $tutor";
            break;
            case 1:
            $tt = "$trainer";
            break;
            case 2:
            $tt = "$tutor";
            break;
        }

        $sql = sprintf(
                "SELECT DISTINCT count(*) as c
                FROM (%s) AS s
                WHERE s.conferma_stato = %d", $tt, $stato);

        $log->log($sql);
        $db->query($sql);
        $ret = $db->fetchassoc()['c'];
        return $ret;
    }

    function registrazioniCountTutor($idSpecializzando, $idTutor)  {
        $rec['registrate'] = _registrazioniCountTutor($idTutor, $idSpecializzando, 0);
        $rec['inviate'] = _registrazioniCountTutor($idTutor, $idSpecializzando, 1);
        $rec['confermate'] = _registrazioniCountTutor($idTutor, $idSpecializzando, 2);
        return $rec;
    }

    $groupSpecvalutazioni->get('/contatori', function (Request $request, Response $response, $args) {
        $log = new Logger();
        $Utils = new Utils();

        $user = $request->getAttribute('user');

        $idScuola = $user['idscuola'];
        $idSpecializzando = $user['id'];
        // $idScuola = "aaf489a8-6143-4389-8da5-6c378836ad4d";
        // $idSpecializzando = "c7f4b007-657d-4f21-9dca-29b98af1f00f";

        $contatori = get_contatori( $idScuola, $idSpecializzando );

        $response->getBody()->write(json_encode($contatori));
        return $response->withHeader('Content-Type', 'application/json');
    });
    $groupSpecvalutazioni->get('/export', function (Request $request, Response $response, $args) {
        $log = new Logger();
        $Utils = new Utils();

        $user = $request->getAttribute('user');
        $p = $request->getQueryParams();

        if ($p['idSpecializzando'] != '' && $user['idruolo'] == 8) {
            return $response
                ->withStatus(400);
        } else if ($p['idSpecializzando'] != '') {
            $idSpecializzando = $p['idSpecializzando'];
        } else {
            $idSpecializzando = $user['id'];
        }

        $specializzando = specializzando_get($idSpecializzando);
        $idScuola = $specializzando['idscuola'];
        $idCoorte = $specializzando['idcoorte'];
        // $idScuola = "aaf489a8-6143-4389-8da5-6c378836ad4d";
        // $idSpecializzando = "c7f4b007-657d-4f21-9dca-29b98af1f00f";

        $contatori = get_export( $idScuola, $idCoorte, $idSpecializzando );
        $res = array(
            "contatori" => $contatori,
            "specializzando" => $specializzando['nome'] . " " . $specializzando['cognome']
        );

        $response->getBody()->write(json_encode($res));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $groupSpecvalutazioni->get('/tutor/{idspecializzando}', function (Request $request, Response $response, $args) {
        $log = new Logger();
        $Utils = new Utils();

        $p = $request->getQueryParams();
        $user = $request->getAttribute('user');
        $specializzando = specializzando_get($args['idspecializzando']);
        // INFO: Sarebbe idturno
        if (!isset($p['id'])) {
            $turni = checkTurniTutor($user['id'], $args['idspecializzando'], $specializzando['idscuola'], false, $specializzando['anno_scuola']);
            // if (sizeof($turni) > 1) {
                $response->getBody()->write(json_encode(array("turni" => $turni)));
                return $response->withHeader('Content-Type', 'application/json');
            // } else {
            //     $p['idturno'] = $turni[0]['id'];
            // }
        }
        // $idScuola = get_idscuola_specializzando($args['idspecializzando']);
        // INFO: Sarebbe idturno
        $valutazione = get_valutazione_tutor($args['idspecializzando'], $specializzando['anno_scuola'], $p['id']);
        if (!$valutazione)  {
            $valutazione = (object)array();
            $valutazione->new = true;
        }
        $valutazione->idturno = $p['id'];

        $response->getBody()->write(json_encode($valutazione));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $groupSpecvalutazioni->get('/direttore/{idspecializzando}', function (Request $request, Response $response, $args) {
        $log = new Logger();
        $Utils = new Utils();

        $p = $request->getQueryParams();
        $user = $request->getAttribute('user');
        $specializzando = specializzando_get($args['idspecializzando']);
        // INFO: Sarebbe idvalutazione
        if (!isset($p['id'])) {
            $turni = checkTurniDirettore($args['idspecializzando'], $specializzando['idscuola'], $specializzando['anno_scuola']);
            if (!$turni){
                $turni = [];
            }
            $log->log(json_encode($turni));
            // INFO: Se vogliamo invece far andare il direttore direttamente alla valutazione basta ripristinare l'if
            // if (sizeof($turni) > 1) {
                $response->getBody()->write(json_encode(array("turni" => $turni)));
                return $response->withHeader('Content-Type', 'application/json');
            // } else {
            //     $p['idturno'] = $turni[0]['id'];
            // }
        }
        // INFO: Sarebbe idvalutazione
        $valutazione = get_valutazione($p['id']);
        if (!$valutazione)  {
            $valutazione = (object)array();
            $valutazione->new = true;
        }

        $valutazione->idturno = $p['idturno'];

        $response->getBody()->write(json_encode($valutazione));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $groupSpecvalutazioni->get('/specializzando', function (Request $request, Response $response, $args) {
        $log = new Logger();
        $Utils = new Utils();

        $specializzando = $request->getAttribute('user');
        $p = $request->getQueryParams();
        if ($p['anno'] && $p['anno'] != 0) {
            $specializzando['anno_scuola'] = $p['anno'];
        }

        $valutazione = get_valutazioni_specializzando($specializzando['id'], $p, $specializzando['anno_scuola'], $p['filtroDal'], $p['filtroAl'] );
        if (!$valutazione)  {
            $valutazione = (object)array();
        }
        $valutazione['anni'] = get_valutazioni_anni($specializzando['id']);

        $response->getBody()->write(json_encode($valutazione));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $groupSpecvalutazioni->get('/specializzando/{idvalutazione}', function (Request $request, Response $response, $args) {
        $log = new Logger();
        $Utils = new Utils();

        $user = $request->getAttribute('user');
        $valutazione = get_valutazione($args['idvalutazione']);

        $response->getBody()->write(json_encode($valutazione));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $groupSpecvalutazioni->post('/{idspecializzando}', function (Request $request, Response $response, $args) {
        $log = new Logger();
        $Utils = new Utils();

        $p = $request->getQueryParams();
        $body = json_decode($request->getBody(), true);
        $user = $request->getAttribute('user');
        $specializzando = specializzando_get($args['idspecializzando']);

        $arValutazione = array(
            "idtutor" => $user['id'],
            "idspecializzando" => $args['idspecializzando'],
            "valutazione" => json_encode($body),
            "anno" => $specializzando['anno_scuola'],
            "idturno" => $p['idturno'],
            "idstatus" => 1
        );

        if (isset($p['idvalutazione']) && $user['idruolo'] == 5) {
            $arConferma = array(
                "conferma_stato" => 1,
                "valutazione" => json_encode($body),
                "idutente_conferma" => $user['id']
            );
            $res = $Utils->dbSql(false, "ssm_valutazioni_tutor", $arConferma, "id", $p['idvalutazione']);
        } else {
            $res = $Utils->dbSql(true, "ssm_valutazioni_tutor", $arValutazione);
        }
        if (!$res['success']){
            $response->getBody()->write("Si è verificato un errore");
            return $response
                ->withHeader('Content-Type', 'text/plain')
                ->withStatus(400);
        }

        return $response->withStatus(200);
    });

    $groupSpecvalutazioni->get('/contatori/{idspecializzando}', function (Request $request, Response $response, $args) {
        $log = new Logger();
        $Utils = new Utils();

        $user = $request->getAttribute('user');
        $idScuola = get_idscuola_specializzando($args['idspecializzando']);
        $contatori = get_contatori($idScuola, $args['idspecializzando']);


        $response->getBody()->write(json_encode($contatori));
        return $response->withHeader('Content-Type', 'application/json');
    });
})->add($authMW);

function get_valutazione($idValutazione)    {
    $utils = new Utils();
    $arSql = array(
        "log" => true,
        "select" => ["*"],
        "from" => "ssm_valutazioni_tutor",
        "where" => [
            [
                "field" => "id",
                "value" => $idValutazione
            ]
        ],
        "decode" => [
            "valutazione"
        ]
    );

    $arrSql = $utils->dbSelect($arSql);
    return $arrSql['data'][0];
}

function checkTurniTutor($idTutor, $idSpecializzando, $idScuola, $all = false, $annoScuola) {
    $utils = new Utils();
    $arSql = array(
        "log" => true,
        "select" => [
            "st.id",
            "CONCAT('Dal ', date_format(st.data_inizio, '%d-%m-%Y'), ' al ', date_format(st.data_fine, '%d-%m-%Y'), ' - ', IF(svt.id, 'Valutato', 'Da valutare')) as text",
            // "st.idtutor"
        ],
        "from" => "ssm_turni st",
        "join" => [
            [
                "ssm_valutazioni_tutor svt",
                "svt.idturno=st.id AND svt.idtutor = '" . $idTutor . "' AND svt.anno=$annoScuola"
            ]
        ],
        "where" => [
            [
                "field" => "st.idspecializzando",
                "value" => $idSpecializzando,
                "operatorAfter" => "AND"
            ],
            [
                "field" => "st.idscuola",
                "value" => $idScuola,
                "operatorAfter" => "AND"
            ],
            [
                "field" => "st.anno",
                "value" => $annoScuola,
                "operatorAfter" => "AND"
            ],
            [
                "field" => "st.idstatus",
                "value" => 1
            ]
        ],
        "order" => "data_inizio"
    );

    if (!$all)  {
        $arSql['where'][3]['operatorAfter'] = "AND";
        $arSql['where'][] = [
            "field" => "st.idtutor",
            "value" => $idTutor
        ];
    }

    $arrSql = $utils->dbSelect($arSql);
    return $arrSql['data'];
}

function checkTurniDirettore($idSpecializzando, $idScuola, $annoScuola) {
    $utils = new Utils();
    $arSql = array(
        "log" => true,
        "select" => [
            "svt.id",
            "CONCAT('Dal ', date_format(st.data_inizio, '%d-%m-%Y'), ' al ', date_format(st.data_fine, '%d-%m-%Y'), ' ', su.nome, ' ', su.cognome, ' - ', IF(svt.conferma_stato = 1, 'Confermata', 'Da confermare')) as text",
            // "st.idtutor"
        ],
        "from" => "ssm_turni st",
        "join" => [
            [
                "ssm_valutazioni_tutor svt",
                "svt.idturno=st.id"
            ],
            [
                "ssm_utenti su",
                "su.id=svt.idtutor"
            ]
        ],
        "where" => [
            [
                "field" => "st.idspecializzando",
                "value" => $idSpecializzando,
                "operatorAfter" => "AND"
            ],
            [
                "field" => "st.idscuola",
                "value" => $idScuola,
                "operatorAfter" => "AND"
            ],
            [
                "field" => "st.anno",
                "value" => $annoScuola,
                "operatorAfter" => "AND"
            ],
            [
                "custom" => "svt.id IS NOT NULL",
                "operatorAfter" => "AND"
            ],
            [
                "field" => "st.idstatus",
                "value" => 1
            ]
        ],
        "order" => "data_inizio"
    );

    $arrSql = $utils->dbSelect($arSql);
    return $arrSql['data'];
}

function get_valutazioni_specializzando($idSpecializzando, $filters, $annoScuola, $filtroDal = "", $filtroAl = "" )  {
    $db = new dataBase();
    $log = new Logger();
    if ($filters != false) {
        $start = $req['p'] != "" ? ($filters['p']-1) * 20 : 0;
        $count = $req['c'] != "" ? ($filters['c']) : 20;
        $sort = $req['srt'] != "" ? $filters['srt'] : "vt.date_create";
        if ($sort == "data_valutazione") {
            $sort = "vt.date_create";
        }
        if ($sort == "nome_tutor") {
            $sort = "su.nome, su.cognome";
        }
        $order = $req['o'] != "" ? $filters['o'] : "desc";

        $paging = sprintf("ORDER BY %s %s LIMIT %d, %d", $db->real_escape_string( $sort ), $db->real_escape_string( $order ), $start, $count);
    }
    $annoFilter = '';
    if ($filter['anno'])    {
        $filtro[] = sprintf("anno=%d", $filters['anno']);
    }
    if( $filtroDal != "" ) {
      $filtro[] = sprintf( "vt.date_create>='%s'", substr( $filtroDal, 0, 10 ) );
    }
    if( $filtroAl != "" ) {
      $filtro[] = sprintf( "vt.date_create<='%s'", substr( $filtroAl, 0, 10 ) );
    }

    if( sizeof( $filtro ) > 0 ) {
      $annoFilter = " AND " . implode( " AND ", $filtro );
    }

    $log->log( "*** " . $filtroDal . " - " . $filtroAl  );

    $sql = sprintf("SELECT SQL_CALC_FOUND_ROWS
            vt.id,
            DATE_FORMAT(vt.date_create, '%%d-%%m-%%Y' ) as data_valutazione,
            vt.anno,
            CONCAT(su.nome, su.cognome) as nome_tutor,
            CONCAT(su2.nome, su2.cognome) as nome_direttore,
            CONCAT(DATE_FORMAT(st.data_inizio, '%%d-%%m-%%Y' ), ' - ', DATE_FORMAT(st.data_fine, '%%d-%%m-%%Y' )) as data_affiancamento
        FROM ssm_valutazioni_tutor vt
        LEFT JOIN ssm_utenti su ON su.id=vt.idtutor
        LEFT JOIN ssm_utenti su2 ON su2.id=vt.idutente_conferma
        LEFT JOIN ssm_turni st ON st.id=vt.idturno
        WHERE vt.idspecializzando='%s' AND vt.anno=%d %s %s", $db->real_escape_string($idSpecializzando), $annoScuola, $annoFilter, $paging);
    $db->query($sql);
    $log->log($sql);

    $ar = array();
    while($rec = $db->fetchassoc()) {
        $ar['rows'][] = $rec;
    }
    $db->query("SELECT FOUND_ROWS()");
    $ar['total'] = intval($db->fetcharray()[0]);
    $ar['count'] = sizeof($ar['rows']);

    return $ar;
}

function get_valutazioni_anni( $idSpecializzando ) {
    $utils = new Utils();
    $arSql = array(
        "select" => ["DISTINCT anno"],
        "from" => "ssm_valutazioni_tutor",
        "where" => [
            [
                "field" => "idspecializzando",
                "value" => $idSpecializzando,
                "operatorAfter" => "AND"
            ]
        ]
    );

    $arrSql = $utils->dbSelect($arSql);
    return $arrSql['data'];
}

function get_valutazione_tutor($idSpecializzando, $anno, $idTurno)    {
    $utils = new Utils();
    $arSql = array(
        "log" => true,
        "select" => ["*"],
        "from" => "ssm_valutazioni_tutor",
        "where" => [
            [
                "field" => "idspecializzando",
                "value" => $idSpecializzando,
                "operatorAfter" => "AND"
            ],
            [
                "field" => "idturno",
                "value" => $idTurno,
                "operatorAfter" => "AND"
            ],
            [
                "field" => "anno",
                "value" => $anno
            ]
        ],
        "decode" => [
            "valutazione"
        ]
    );

    $arrSql = $utils->dbSelect($arSql);
    return $arrSql['data'][0];
}

function get_contatori( $idScuola, $idSpecializzando )    {

    $db = new dataBase();
    $sql = sprintf("SELECT
	    cc.nome, SUM(r.quantita) as quantita, cc.autonomia, cc.quantita as quantita_totale
        FROM ssm_registrazioni r
        LEFT JOIN ssm.ssm_pds_coorti_contatori cc ON JSON_CONTAINS( r.contatori, CAST( CONCAT( '\"', cc.id, '\"' ) AS JSON ), '$' )
        WHERE r.idutente = '%s'
        AND cc.idscuola_specializzazione = '%s'
        GROUP BY cc.nome, cc.autonomia, cc.quantita", $db->real_escape_string( $idSpecializzando ), $db->real_escape_string( $idScuola ) );
    // echo $sql;
    $db->query($sql);
    $contatori = array();
    while ($rec = $db->fetchassoc()) {
        $rec['autonomia'] = json_decode($rec['autonomia'], true);
        $rec['autonomia_raggiunta'] = calc_autonomia_raggiunta($rec['autonomia'], $rec['quantita']);
        $perc = round($rec['quantita'] * 100 / $rec['quantita_totale'], 2);
        $rec['percentuale_completamento'] = ($perc <= 100 ? $perc : "100") . "%";
        $rec['percentuale_completamento_num'] = $perc <= 100 ? $perc : 100;
        $contatori[] = $rec;
    }
    return $contatori;
}

function get_export($idScuola, $idCoorte, $idSpecializzando)
{
    $db = new dataBase();
    $log = new Logger();
    $sql = sprintf("SELECT nome, quantita as quantita_totale, struttura, frequenza
        FROM ssm.ssm_pds_coorti_export
        WHERE idscuola_specializzazione = '%s' AND idcoorte = '%s' AND idstatus = 1 ORDER BY nome", $db->real_escape_string( $idScuola ), $db->real_escape_string( $idCoorte ) );
    $db->query($sql);
    $log->log($sql);
    $contatori = array();
    while ($rec = $db->fetchassoc()) {
        $log->log(json_encode($rec));
        $rec['quantita_registrazioni'] = calc_quantita_registrazioni(json_decode($rec['struttura'], true), $idScuola, $idSpecializzando, $rec['frequenza']);
        $perc = round($rec['quantita_registrazioni']['convalidate'] * 100 / $rec['quantita_totale'], 2);
        $rec['percentuale_completamento'] = ($perc <= 100 ? $perc : "100") . "%";
        $rec['percentuale_completamento_num'] = $perc <= 100 ? $perc : 100;
        unset($rec['struttura']);
        $contatori[] = $rec;
    }
    return $contatori;
}

function calc_quantita_registrazioni($struttura, $idScuola, $idSpecializzando, $frequenza)    {

    $db = new dataBase();
    $log = new Logger();

    $idcombo_prestazione = _get_id_combo_prestazione($idScuola);

    // TODO: SQL_INJECTION_TEST
    foreach( $struttura as $k => $v ) {
      $s .= " OR ";
      foreach ($v as $kk => $vv) {
          $s .= "(";
        foreach ($vv['idvalue'] as $a => $b) {
            if ($vv['id'] == 'idattivita')  {
                $s .= sprintf("idattivita = '%s'", $db->real_escape_string( $b ) );
            } else if ($vv['id'] == $idcombo_prestazione) {
                $s .= sprintf("idprestazione = '%s'", $db->real_escape_string( $b ) );
            } else {
                // $vv['id'] != $idcombo_prestazione
                $s .= sprintf(
                    "( JSON_CONTAINS( JSON_EXTRACT(struttura_full, '$[*].idvalue'), '\"%s\"', '$' )
                    AND JSON_CONTAINS( JSON_EXTRACT(struttura_full, '$[*].id'), '\"%s\"', '$' ) )",
                    $db->real_escape_string( $b ),
                    $db->real_escape_string( $vv['id'] )
                );
            }
            $s .= " OR ";
        }
        $s = substr($s, 0, -3);
        $s .= ") AND ";
      }
      $s = substr($s, 0, -4);
    }
    $s = substr($s, 3);

    $quantita = $frequenza ? "SUM(1)" : "SUM(quantita)";
    $rec = array();
    // Registrate
    if ($frequenza) {
        $sql = sprintf("SELECT DISTINCT data_registrazione, %s as quantita FROM ssm_registrazioni WHERE idutente = '%s' AND idstatus = 1 AND ( %s )", $quantita, $db->real_escape_string($idSpecializzando), $s);
    } else {
        $sql = sprintf("SELECT %s as quantita FROM ssm_registrazioni WHERE idutente = '%s' AND idstatus = 1 AND ( %s )", $quantita, $db->real_escape_string($idSpecializzando), $s);
    }
    $log->log($sql);
    $db->query($sql);
    $ret = $db->fetchassoc();
    $rec['registrate'] = $ret['quantita'];

    if ($frequenza) {
        $sql = sprintf("SELECT DISTINCT data_registrazione, %s as quantita FROM ssm_registrazioni WHERE idutente = '%s' AND idstatus = 1 AND conferma_stato = 2 AND ( %s )", $quantita, $db->real_escape_string( $idSpecializzando ), $s);
    } else {
        $sql = sprintf("SELECT %s as quantita FROM ssm_registrazioni WHERE idutente = '%s' AND idstatus = 1 AND conferma_stato = 2 AND ( %s )", $quantita, $db->real_escape_string($idSpecializzando), $s);
    }
    $db->query($sql);
    $ret = $db->fetchassoc();
    $rec['convalidate'] = $ret['quantita'];

    return $rec;
}

function _get_id_combos_attivita($idScuola, $idCoorte) {
    $utils = new Utils;

    $arSql = array(
        "log" => true,
        "select" => ["id", "nome as text"],
        "from" => "ssm.ssm_registrazioni_attivita",
        "where" => [
            [
                "field" => "idscuola",
                "value" => $idScuola,
                "operatorAfter" => "AND"
            ],
            [
                "field" => "idcoorte",
                "value" => $idCoorte,
                "operatorAfter" => "AND"
            ],
            [
                "field" => "idstatus",
                "value" => 1
            ]
        ]
    );

    $arrSql = $utils->dbSelect($arSql);
    return $arrSql['data'];
}

function calc_autonomia_raggiunta($autonomia, $quantita)    {
    $log = new Logger();
    foreach ($autonomia as $v)  {
        $log->log(json_encode($v));
        if ($quantita >= $v['livello_da'] && $quantita <= $v['livello_a'])  {
            $autonomia_raggiunta = $v['autonomia'];
            break;
        }
    }
    return $autonomia_raggiunta;
}

function get_idscuola_specializzando($idSpecializzando) {
    $utils = new Utils();

    $arSql = array(
        "select" => ["idscuola"],
        "from" => "ssm_utenti_ruoli_lista",
        "where" => [
            [
                "field" => "idutente",
                "value" => $idSpecializzando
            ]
        ]
    );
    $arrSql = $utils->dbSelect($arSql);
    return $arrSql['data'][0]['idscuola'];
}

function scuoleList($idAteneo, $idscuola = "" )    {
    $Utils = new Utils();

    $arSql = array(
        "select" => ["sa.id", "s.nome_scuola as text"],
        "from" => "ssm.ssm_scuole_atenei sa",
        "join" => [
            [
                "ssm.ssm_scuole s",
                "s.id=sa.idscuola"
            ]
        ],
        "where" => [
            [
                "field" => "sa.idstatus",
                "value" => "1",
                "operatorAfter" => "AND"
            ],
            [
                "field" => "sa.idateneo",
                "value" => $idAteneo
            ]
        ],
        "order" => "s.nome_scuola asc"
    );

    $arrSql = $Utils->dbSelect($arSql);
    return $arrSql['data'];
}
