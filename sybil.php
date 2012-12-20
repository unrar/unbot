<?php 
/* Sybil IRC Bot *
 * Freenode: UnRar
 * Wikipedia: UnRar
 */
//Cargamos la API de UNDB
require("udb_connector.php");
//Primero, configuramos las variables
$host = "irc.freenode.net"; //Conectamos a FreeNode
$port = 6667; //Puerto 6667
$nick = "unbot";
$ident = "unbotillo";
$realname = "Sybil 2012.12.17b + UNDB Sybil 2012.12.16";
$passwd = "openaccess";
//Dueño (máscara)
$owner = "wikimedia\/unrar";
//Canal al que entraremos
$chan = "#wikipedia-es-bots";
// Canales
$chans = array("#undb");
#$chans = array("#wikipedia-es", "#sandyd", "#undb", "#undb-es");
// Prevenimos el timelimit de PHP
echo "Conectando a ".$host."...\n";
// Abrimos el socket
set_time_limit(0);
$socket = fsockopen($host, $port);
// Nos identificamos y entramos
fputs($socket, "NICK ".$nick."\n");
fputs($socket, "USER ".$ident." ".$host." bla :".$realname."\n");
fputs($socket, "PRIVMSG NickServ :IDENTIFY ".$passwd."\n");
fputs($socket, "JOIN :".$chan."\n");
foreach ($chans as $t_chan) {
	fputs($socket, "JOIN :".$t_chan."\n");
}
echo "¡Conectado correctamente! He entrado a ".$chan."\n";
// Entramos en un bucle infinito
while(1) {
	while($data = fgets($socket, 128)) {
		echo "Server: ".$data."\n";		
		//Antes de separar, quito \n\r
		$command = str_replace(array(chr(10), chr(13)), '', $data);
		//Separamos los datos en un array
		$ex = explode(' ', $command);
		//Creo una variable con todo el mensaje, de $ex[3] al final
		$aparams = array_slice($ex,3);
		//Lo junto
		$aparams = implode(' ',$aparams);
		//Creo un array con los parámetros de después de $ex[3]
		$params = array_slice($ex,4);
		//Los junto
		$rparams = implode(' ', $params);
		//¿Ping? ¡PONG!
		if ($ex[0] == "PING") {	
			fputs($socket, "PONG ".$ex[1]."\r\n");
		}
		//COMANDOS
		//Evalúo PRIVMSGs
		if ($ex[1] == "PRIVMSG") {
			//Quitamos los :
			$masktemp = str_replace(chr(58), '', $ex[0]);
			//Separamos la mascara
			// $mask[0] => nick
			// $mask[1] => ident@host
			$mask = explode('!', $masktemp);

			echo "Recibido PRIVMSG. De: ".$mask[0].". En: ".$ex[2].". Mensaje: ".$ex[3].".\n";
			//Si es un query
			if (strtolower($ex[2]) == strtolower($nick)) {
				//1. Junto los parametros de $ex[4] para arriba.
				$locparams = implode(' ', $params);
				//2. Separo $ex[3] por secciones dedos puntos
				//:Hola => '', 'Hola'
				//:Hola: => '', 'Hola', ''
				$tempsp = explode(':',$ex[3]);
				//3. Ahora cojo solo desde el primer parametro
				//'', 'Hola' => 'Hola'
				//'', 'Hola', '' => 'Hola', ''
				$tempsp = array_splice($tempsp,1);
				//4. Finalmente, lo junto para restaurar los dospuntos, si habia (y elimino el array)
				//'Hola' => Hola
				//'Hola', '' => Hola:
				$primera = implode(':', $tempsp);
				//Lo digo en el canal
				//fputs($socket, "PRIVMSG ".$chan." :".chr(2).chr(3)."04¡Atención!".chr(15)." ".$mask[0]." (".$mask[1].") me ha dicho en un query: ".$primera." ".$locparams."\n");
				//Destruyo lo que no me hace falta
				unset($locparams);
				unset($primera);
				unset($tempsp);
			}
			//Si me nombran...
			if (preg_match("/".$nick."/i", $aparams)) {
				#fputs($socket, "PRIVMSG ".$ex[2]." :¡Eh ".$mask[0].", me gastarás el nombre!\n");
			}
			if (strtolower($ex[3]) == ":%quit") {
				if (preg_match("/".$owner."/i", $mask[1])) {
					fputs($socket, "QUIT :I don't like to live in a yellow submarine...\n");
					exit();
				} else {
					fputs($socket, "PRIVMSG ".$ex[2]." :¡Error! No tienes autorización para ejecutar este comando.\n");
				}
				
			}
			if (strtolower($ex[3]) == ":%ping") {
				fputs($socket, "PRIVMSG ".$ex[2]." :".$mask[0].": ¡PONG!\n");
			}
			if (strtolower($ex[3]) == ":%di") {
				if (preg_match("/".$owner."/i", $mask[1])) {
					//En este caso $params no me vale, tengo que coger del 5 para arriba
					//por lo que directamente haré del 1 hacia arriba de $params ($params[0] => $ex[4])
					$mensaje = array_slice($params,1);
					//Ahora lo junto con implode
					$mensaje = implode(' ',$mensaje);
					fputs($socket, "PRIVMSG ".$ex[4]." :".$mensaje."\n");
					fputs($socket, "PRIVMSG ".$ex[2]." :He mandado un ".chr(2)."PRIVMSG ".chr(2)."a ".$ex[4]." con el texto ".$mensaje."\n");
					//Ya no me sirve esa variable
					unset($mensaje);
				} else {
					fputs($socket, "PRIVMSG ".$ex[2]." :¡Error! No tienes autorización para ejecutar este comando.\n");
				}
	
			}
			if (strtolower($ex[3]) == ":%join") {
				//Compruebo si tiene permiso ($owner), ¡en case insensitive!
				if (preg_match("/".$owner."/i", $mask[1])) {
					fputs($socket, "JOIN ".$ex[4]."\n");
					fputs($socket, "PRIVMSG ".$ex[2]." :¡He entrado correctamente al canal ".$ex[4]."!\n");
					fputs($socket, "PRIVMSG ".$ex[4]." :¡Hola! Me ha enviado ".$mask[0].".\n");
				} else {
					fputs($socket, "PRIVMSG ".$ex[2]." :¡Error! No tienes autorización para ejecutar este comando.\n");
				}
			}
			if (strtolower($ex[3]) == ":%part") {
				//Compruebo si tiene permiso ($owner), ¡en case insensitive!
				if (preg_match("/".$owner."/i", $mask[1])) {
					fputs($socket, "PRIVMSG ".$ex[4]." :Tengo que despedirme... ¡".$mask[0]." quiere que me vaya!\n");
					fputs($socket, "PART ".$ex[4]."\n");
					fputs($socket, "PRIVMSG ".$ex[2]." :¡He salido del canal ".$ex[4]."!\n");
				} else {
					fputs($socket, "PRIVMSG ".$ex[2]." :¡Error! No tienes autorización para ejecutar este comando.\n");
				}
			}
			//Asociar un nick
			if ((strtolower($ex[3]) == ":%nas") or (strtolower($ex[3]) == ":%nariz")) {
				if (!$ex[4]) {
					// Conectamos
					$u = new UDB_Connector();
					$u_cc = $u->connect("nas.udb");
					if ($u_cc == false) goto narizend;
					
					$udb_nas = fopen("cache.ucb", "r");
					$nas_nicks = array();
					while (!feof($udb_nas)) {
						$nas_line = fgets($udb_nas);
						$nas_expl = explode('~!', $nas_line);
						foreach ($nas_expl as $nas_vnick) {
							// Unparse $nas_vnick
							$nas_vnick = dp_chars($nas_vnick);
							if (trim($nas_vnick) == $mask[0]) $nas_found = true;
							array_push($nas_nicks, trim($nas_vnick)); 
						}
							if ($nas_found == true) {
								$udb_dbo = array_shift($nas_nicks);
								fputs($socket, "PRIVMSG ".$ex[2]." :Tu cuenta ".$nas_expl[0]." tiene los siguientes nicks asociados: ".chr(3)."12".implode(", ",$nas_nicks).chr(15).".\n");
								break;
							}
							$nas_nicks = array();
						}
						if ($nas_found != true) { 
							fputs($socket, "PRIVMSG ".$ex[2]." :Tu nick no está asociado a ninguna cuenta.\n");
						} else {
							unset($nas_found);
						}
					fclose($udb_nas);
					narizend:
				} else {
					$udb_ne = nick_exists_udb(p_chars($mask[0]));
					if ($udb_ne != false) {
						fputs($socket, "PRIVMSG ".$ex[2]." :Lo siento, el nick ".chr(3)."12".$mask[0].chr(15)." ya está asignado a una cuenta.\n");
					} else {
						
						$uu = new UDB_Connector();
						$uu_cc = $uu->connect("nas.udb");
						if ($uu_cc == false) goto narizend2;
						$found = false;
						$pudb_wnas = file($file, FILE_IGNORE_NEW_LINES);
						$nick = $mask[0];
						foreach ( $pudb_wnas as $k => &$line ) {
							$found = preg_match(sprintf("/^%s~/", preg_quote($ex[4])), $line) and $line = sprintf("%s~!%s", $line, $nick);
						}
						if ($found) {
							file_put_contents($file, implode(PHP_EOL, $pudb_wnas));
						} else {
						$udb_wnas = fopen("cache.ucb", "a+");
						fwrite($udb_wnas, preg_quote($ex[4])."~!".$mask[0]."\n");
						fclose($udb_wnas);
						}
						fputs($socket, "PRIVMSG ".$ex[2]." :Añadido correctamente tu nick ".chr(3)."12".$mask[0].chr(15)." a la cuenta ".$ex[4].".\n");
						$uu->save("nas.udb");
						narizend2:
					}
				}
			}
			// IP!
			if (strtolower($ex[3]) == ":%ip") {
			    //CheckSyntax
			    if (!$ex[4]) {
				fputs($socket, "PRIVMSG ".$ex[2]." :".chr(2).$mask[0].chr(2).", ¡la sintaxis del comando es ".chr(3)."12%ip xxx.xxx.xxx.xxx".chr(15)."!\n");
			    } else {
				//Descargamos la página
				$wget = system('wget -O ip.txt http://whatismyipaddress.com/ip/'.trim($ex[4]));
				$lines = file('ip.txt');
				foreach ($lines as $linea) {
				    if (preg_match("/Country/",$linea)) {
					$ter = explode("<",$linea);
					$pter = explode(">", $ter[4]);
					$pter = str_replace(" ", "", $pter);
					#fputs($socket, "PRIVMSG ".$ex[2]." :País: ".$pter[1].".\n");
					$g_country = $pter[1];
					unset($ter,$pter);
					#<tr><th>Country:</th><td>Mexico <img src="http://cdn.whatismyipaddress.com/images/flags/mx.png" alt="mx flag"> </td></tr>
				    }
				    if (preg_match("/PAC:  --><!-- rDNS:/i", $linea)) {
					$ter = explode("rDNS: ", $linea);
					$pter = explode(" -->", $ter[1]);
					#fputs($socket, "PRIVMSG ".$ex[2]." :Host: ".$pter[0].".\n");
					$g_host = $pter[0];
					unset($ter,$pter);
					
					$ter = explode("ISP:<", $linea);
					$qter = explode("<", $ter[1]);
					$pter = explode(">", $qter[1]);
					$g_isp = $pter[1];
					unset($ter,$qter,$pter);
					#<!-- PAC:  --><!-- rDNS: dsl-189-151-222-83-dyn.prod-infinitum.com.mx --><!-- score: 0 --><form action="/blacklist-check" method="POST"><table><tr><th>IP:</th><td>189.151.222.83</td></tr><tr><th>Decimal:</th><td>3180846675</td></tr><tr><th>Hostname:</th><td>dsl-189-151-222-83-dyn.prod-infinitum.com.mx</td></tr><tr><th>ISP:</th><td>Gestión de direccionamiento UniNet</td></tr><tr><th>Organization:</th><td>Gestión de direccionamiento UniNet</td></tr><tr><th>Services:</th><td>None detected</td></tr><tr><th>Type:</th><td><a href='/broadband'>Broadband</a></td></tr><tr><th>Assignment:</th><td><a href='/dynamic-static'>Dynamic IP</a></td></tr><tr><th>Blacklist:</th><td><input type='hidden' name='LOOKUPADDRESS' value='189.151.222.83'><input type='SUBMIT' name='Lookup Hostname' value='Blacklist Check'></td></tr></table></form>

				    }
				    if (preg_match("/State\/Region/i", $linea)) {
					$ter = explode("<",$linea);
					$pter = explode(">", $ter[4]);
					#fputs($socket, "PRIVMSG ".$ex[2]." :Región: ".$pter[1].".\n");
					$g_region = $pter[1];
					unset($ter,$pter);
						 #<tr><th>State/Region:</th><td>Puebla</td></tr>
				    }
				    if (preg_match("/City:</i", $linea)) {
					$ter = explode("<", $linea);
					$pter = explode(">", $ter[4]);
					#fputs($socket, "PRIVMSG ".$ex[2]." :Ciudad: ".$pter[1].".\n");
					$g_city = $pter[1];
					unset($ter,$pter);
					#<tr><th>City:</th><td>Puebla</td></tr>
				    }
				    if (preg_match("/Latitude:</i", $linea)) {
					$ter = explode("<", $linea);
					$pter = explode(">", $ter[4]);
					$g_latitude = $pter[1];
					#<tr><th>Latitude:</th><td>19.05</td></tr>
				    }
				    if (preg_match("/Longitude:</i", $linea)) {
					$ter = explode("<", $linea);
					$pter = explode(">", $ter[4]);
					$g_longitude = $pter[1];
					#<tr><th>Longitude:</th><td>-98.2</td></tr>
				    }
				    if (preg_match("/<\/html>/i", $linea)) {
					fputs($socket, "PRIVMSG ".$ex[2]." :Información de la IP ".chr(2).$ex[4].chr(2).": Host: ".chr(3)."03".utf8_encode($g_host).chr(15)." - Localización: ".chr(3)."06".utf8_encode($g_city).", ".utf8_encode($g_region).", ".utf8_encode($g_country).chr(15)." - Coordenadas: ".chr(3)."04 ".utf8_encode($g_latitude).", ".utf8_encode($g_longitude).chr(15)." - ISP: ".chr(3)."13".utf8_encode($g_isp).chr(15)." - Bloquear: ".chr(3)."10https://es.wikipedia.org/wiki/Especial:Bloquear/".str_replace(".", "%2E", $ex[4]).chr(15)." - Más información: ".chr(3)."12http://www.whatismyipaddress.com/ip/".$ex[4].chr(15).".\n");
				    }
				}
			    }
			}
			if (strtolower($ex[3]) == ":%gatos" or strtolower($ex[3]) == ":%cats") {
				if (!$ex[4]) {
				    fputs($socket, "PRIVMSG ".$ex[2]." :".chr(2).$mask[0].chr(2).", ¡la sintaxis del comando es ".chr(3)."12%cats Nombre Artículo".chr(15)."!\n");
				    goto fin;
				}
				$rparamos = $rparams;
				$rparams = str_replace(" ", "_", $rparams);
				$rparams = str_replace("(", "%28", $rparams);
				$rparams = str_replace(")", "%29", $rparams);
				if (preg_match("/^en:/i", $ex[4])) {
				    $rparams = preg_replace("/^en:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://en.wikipedia.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} else if (preg_match("/^wikt:/i", $ex[4])) {
				    $rparams = preg_replace("/^wikt:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://es.wiktionary.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} else if (preg_match("/^n:/i", $ex[4])) {
				    $rparams = preg_replace("/^n:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://es.wikinews.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} else if (preg_match("/^b:/i", $ex[4])) {
				    $rparams = preg_replace("/^b:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://es.wikibooks.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} else if (preg_match("/^q:/i", $ex[4])) {
				    $rparams = preg_replace("/^q:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://es.wikiquote.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} else if (preg_match("/^s:/i", $ex[4])) {
				    $rparams = preg_replace("/^s:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://es.wikisource.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} else if (preg_match("/^v:/i", $ex[4])) {
				    $rparams = preg_replace("/^v:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://es.wikiversity.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} else if (preg_match("/^species:/i", $ex[4])) {
				    $rparams = preg_replace("/^species:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://es.wikispecies.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} else if (preg_match("/^commons:/i", $ex[4])) {
				    $rparams = preg_replace("/^commons:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://commons.wikimedia.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} else  if (preg_match("/^meta:/i", $ex[4])) {
				    $rparams = preg_replace("/^meta:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://meta.wikimedia.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} else if (preg_match("/^wikimedia:/i", $ex[4])) {
				    $rparams = preg_replace("/^wikimedia:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://wikimediafoundation.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} else if (preg_match("/^incubator:/i", $ex[4])) {
				    $rparams = preg_replace("/^incubator:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://incubator.wikimedia.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} elseif (preg_match("/^mw:/i", $ex[4])) {
				    $rparams = preg_replace("/^mw:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://www.mediawiki.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				} else if (preg_match("/^es:/i", $ex[4])) {
				    $rparams = preg_replace("/^es:/i", "", $rparams);
				    $wget = system('wget -O resulta.txt http://es.wikipedia.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				}  else {
				    $wget = system('wget -O resulta.txt http://es.wikipedia.org/w/api.php?action=query\&prop=categories\&titles='.$rparams,$wgeto);
				}
				$lines = file('resulta.txt');
				$tmpcheck = preg_replace("/&quot;/", "", $lines[20]);
				$tmpcheck = str_replace("\t", "", $tmpcheck);
				$tmpcheck = preg_replace("/title=\"/", "", $lines[20]);
				$tmpcheck = preg_replace("/&lt;/", "", $lines[20]);
				$tmpcheck = preg_replace("/&gt;/", "", $lines[20]);
				$tmpcheck = preg_replace("/\/&gt;/", "", $lines[20]);
				if (preg_match("/missing=/", $tmpcheck)) {
					fputs($socket, "PRIVMSG ".$ex[2]." :¡Error! El artículo ".chr(2).$rparamos.chr(15)." no existe.\n");
				} else {
					$cats = array();
					foreach($lines as $linea) {
						//$linea = $lines($count);
						//Limpiamos los &quot;
						$linea = preg_replace("/&quot;/", "", $linea);
						//Limpiamos el tab
						$linea = str_replace("\t", "", $linea);
						//Si hay coma al final, la borramos
						$linea = preg_replace("/,$/", "", $linea);
						//Limpiamos saltos de línea
						$linea = str_replace("\n", "", $linea);
						$linea = preg_replace("/title=\"/", "", $linea);
						$linea = preg_replace("/&lt;/", "", $linea);
						$linea = preg_replace("/&gt;/", "", $linea);
						$linea = preg_replace("/\/&gt;/", "", $linea);
						$linea = str_replace("<span style=\"color:blue;\">cl ns=14 title=", "", $linea);
						$linea = str_replace("/</span>", "", $linea);
						if ((preg_match("/Categoría/", $linea)) or (preg_match("/Category/", $linea))) {
						 $cat = explode(':', $linea);
						 $cat = array_slice($cat, 1);
						 $cat = implode(':', $cat);
						 $cats[] = $cat;
						 //echo "Categoría: ".$cat[1]."\n";
						 }
						//Ahora, actuamos según lo que haya en la primera parte
						$linea = explode(' ', $linea);
						$seccion = $linea[0];
						//Quitamos coma de la sección, si hay (grupos)
						$seccion = preg_replace("/,$/", "", $seccion);
					}
					$cats = implode(',', $cats);
					$cats = preg_replace("/ ,/", ", ", $cats);
					$cats = preg_replace("/ $/", ".", $cats);
					$vmsg = str_replace("Ã", "í", "Categorías de ".chr(2).$rparamos.chr(15).": ".$cats);
					if (strlen($vmsg) >= 373) {
					    $resto = strlen($vmsg) - 370;
					    $wmsg = substr($vmsg, -$resto)."\n";
					    $vmsg = substr($vmsg,0,370).chr(2).chr(3)."12 ".mb_convert_encoding("&#8601;", 'UTF-8',  'HTML-ENTITIES').chr(15)."\n";
					    if (strlen($wmsg) >= 373) {
					    $restox = strlen($wmsg) - 370;
					    $xmsg = substr($wmsg, -$restox)."\n";
					    $wmsg = substr($wmsg,0,370).chr(2).chr(3)."12 ".mb_convert_encoding("&#8601;", 'UTF-8',  'HTML-ENTITIES').chr(15)."\n";   
					    } else {
					    $wmsg .= "\n";
					    }
					} else {
					    $vmsg .= "\n";
					}
					fputs($socket,"PRIVMSG ".$ex[2]." :".utf8_encode($vmsg));
					if ($wmsg) {
					fputs($socket,"PRIVMSG ".$ex[2]." :".utf8_encode($wmsg));
					unset($wmsg);
					}
					if ($xmsg) { 
					fputs($socket,"PRIVMSG ".$ex[2]." :".utf8_encode($xmsg));
					unset($wmsg);
					}
					#fputs($socket,"PRIVMSG ".$ex[2]." :".chr(3)."12[1]".chr(3)."02 http://es.wikipedia.org/wiki/".$rparams."\n");
				}
			    fin:
			}
			if (strtolower($ex[3]) == ":%ayuda") {
				if ($ex[4] == "info") {
					fputs($socket, "PRIVMSG ".$ex[2]." :Muestra información sobre un nick en el proyecto especificado. Uso: ".chr(3)."12%info [proyecto:]usuario".chr(15).".\n");
				} elseif ($ex[4] == "gatos" or $ex[4] == "cats") {
					fputs($socket, "PRIVMSG ".$ex[2]." :Muestra las categorías del artículo dado. Uso: ".chr(3)."12%gatos Artículo".chr(15).".\n");
				} elseif ($ex[4] == "ping") {
					fputs($socket, "PRIVMSG ".$ex[2]." :Te hace ping, para ver si te has caído. Uso: ".chr(3)."12%ping".chr(15).".\n");
				} elseif ($ex[4] == "di") {
					fputs($socket, "PRIVMSG ".$ex[2]." :Manda un mensaje a un canal/persona (query). Uso: ".chr(3)."12%di {#canal|nick} mensaje ...".chr(15).".\n");
				} elseif ($ex[4] == "join") {
					fputs($socket, "PRIVMSG ".$ex[2]." :Entra a un canal. Uso: ".chr(3)."12%join #canal".chr(15).".\n");
				} elseif ($ex[4] == "part") {
					fputs($socket, "PRIVMSG ".$ex[2]." :Sale de un canal. Uso: ".chr(3)."12%part #canal".chr(15).".\n");
				} elseif ($ex[4] == "quit") {
					fputs($socket, "PRIVMSG ".$ex[2]." :Sale del IRC. Uso: ".chr(3)."12%quit".chr(15).".\n");
				} elseif ($ex[4] == "awiki") {
					fputs($socket, "PRIVMSG ".$ex[2]." :Muestra el enlace del artículo especificado en el proyecto (opcional) indicado (con prefijo wikt:Prueba, por ejemplo). Uso: ".chr(3)."12%awiki [prefijo:]artículo".chr(15).".\n");
				} elseif ($ex[4] == "ip") {
					fputs($socket, "PRIVMSG ".$ex[2]." :Muestra información relativa a una IP, incluyendo un link para bloquearla. Uso: ".chr(3)."12%ip xxx.xxx.xxx.xxx".chr(15).".\n");
				} elseif (($ex[4] == "nas") or ($ex[4] == "nariz")) {
					fputs($socket, "PRIVMSG ".$ex[2]." :Sin parámetros, muestra a qué cuentas estas asociado. Con parámetros, te añade a una cuenta. Uso: ".chr(3)."12%nas [nombre cuenta]".chr(15).".\n");
				} else {
				
				fputs($socket, "PRIVMSG ".$ex[2]." :¡Hola! Soy el robot ".chr(3)."05".$nick.chr(15).". Mis comandos (prefijados con \"%\") son: ".chr(3)."12info, gatos (cats), awiki, di, join, part, quit, ping, ip, nas.\n");
				fputs($socket, "PRIVMSG ".$ex[2]." :Para más información, pon: ".chr(3)."12%ayuda ".chr(2)."elcomando".chr(15).".\n");
				}
			}
			if (strtolower($ex[3]) == ":%awiki") {
			    if (!$ex[4]) {
			  fputs($socket, "PRIVMSG ".$ex[2]." :".chr(2).$mask[0].chr(2).", ¡la sintaxis del comando es ".chr(3)."12%awiki Nombre Artículo".chr(15)."!\n");
			 } else {
				$rparams = str_replace(" ", "_", $rparams);
				    if (preg_match("/^en:/i", $ex[4])) {
					$rparams = preg_replace("/^en:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://en.wikipedia.org/wiki/".$rparams."\n");
				    } else if (preg_match("/^wikt:/i", $ex[4])) {
					$rparams = preg_replace("/^wikt:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://es.wiktionary.org/wiki/".$rparams."\n");
				    } else if (preg_match("/^n:/i", $ex[4])) {
					$rparams = preg_replace("/^n:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://es.wikinews.org/wiki/".$rparams."\n");
				    } else if (preg_match("/^b:/i", $ex[4])) {
					$rparams = preg_replace("/^b:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://es.wikibooks.org/wiki/".$rparams."\n");
				    } else if (preg_match("/^q:/i", $ex[4])) {
					$rparams = preg_replace("/^q:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://es.wikiquote.org/wiki/".$rparams."\n");
				    } else if (preg_match("/^s:/i", $ex[4])) {
					$rparams = preg_replace("/^s:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://es.wikisource.org/wiki/".$rparams."\n");
				    } else if (preg_match("/^v:/i", $ex[4])) {
					$rparams = preg_replace("/^v:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://es.wikiversity.org/wiki/".$rparams."\n");
				    } else if (preg_match("/^species:/i", $ex[4])) {
					$rparams = preg_replace("/^species:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://es.wikispecies.org/wiki/".$rparams."\n");
				    } else if (preg_match("/^commons:/i", $ex[4])) {
					$rparams = preg_replace("/^commons:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://commons.wikimedia.org/wiki/".$rparams."\n");
				    } else  if (preg_match("/^meta:/i", $ex[4])) {
					$rparams = preg_replace("/^meta:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://meta.wikimedia.org/wiki/".$rparams."\n");
				    } else if (preg_match("/^wikimedia:/i", $ex[4])) {
					$rparams = preg_replace("/^wikimedia:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://wikimediafoundation.org/wiki/".$rparams."\n");
				    } else if (preg_match("/^incubator:/i", $ex[4])) {
					$rparams = preg_replace("/^incubator:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://incubator.wikimedia.org/wiki/".$rparams."\n");
				    } elseif (preg_match("/^mw:/i", $ex[4])) {
					$rparams = preg_replace("/^mw:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://www.mediawiki.org/wiki/".$rparams."\n");
				    } else if (preg_match("/^es:/i", $ex[4])) {
					$rparams = preg_replace("/^es:/i", "", $rparams);
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://es.wikipedia.org/wiki/".$rparams."\n");
				    }  else {
					fputs($socket, "PRIVMSG ".$ex[2]." :".chr(3)."02[1] ".chr(3)."02http://es.wikipedia.org/wiki/".$rparams."\n");
				    }
				}
			}
			if (strtolower($ex[3]) == ":%info") {
				//Si está en la UDB el nick, consultamos
				$ni_udb = nick_exists_udb($mask[0]);
				/*if (($ni_udb != false) && (!$ex[4])) {
					$wget = system('wget -O resulta.txt http://es.wikipedia.org/w/api.php?action=query\&list=users\&ususers='.$ni_udb.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
					goto parseinfo;
				} */
				//Replace
				$rparams = str_replace(" ", "_", $rparams);
				if (preg_match("/^en:/i", $ex[4])) {
			    	$rtem = explode("en:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
			    	$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
			    	$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$wget = system('wget -O resulta.txt http://en.wikipedia.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} else if (preg_match("/^wikt:/i", $ex[4])) {
			    	$rtem = explode("wikt:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
			    	$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
			    	$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$wget = system('wget -O resulta.txt http://es.wiktionary.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} else if (preg_match("/^n:/i", $ex[4])) {
			    	$rtem = explode("n:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
			    	$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$wget = system('wget -O resulta.txt http://es.wikinews.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} else if (preg_match("/^b:/i", $ex[4])) {
			    	$rtem = explode("b:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
			    	$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
				$wget = system('wget -O resulta.txt http://es.wikibooks.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} else if (preg_match("/^q:/i", $ex[4])) {
			    	$rtem = explode("q:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
				$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
				$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$wget = system('wget -O resulta.txt http://es.wikiquote.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} else if (preg_match("/^s:/i", $ex[4])) {
			    	$rtem = explode("s:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
				$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
				$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$wget = system('wget -O resulta.txt http://es.wikisource.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} else if (preg_match("/^v:/i", $ex[4])) {
			    	$rtem = explode("v:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
				$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
				$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$wget = system('wget -O resulta.txt http://es.wikiversity.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} else if (preg_match("/^species:/i", $ex[4])) {
			    	$rtem = explode("species:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
				$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
				$wget = system('wget -O resulta.txt http://es.wikispecies.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} else if (preg_match("/^commons:/i", $ex[4])) {
			    	$rtem = explode("commons:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
				$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
				$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$wget = system('wget -O resulta.txt http://commons.wikimedia.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} else  if (preg_match("/^meta:/i", $ex[4])) {
			    	$rtem = explode("meta:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
				$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
				$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$wget = system('wget -O resulta.txt http://meta.wikimedia.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} else if (preg_match("/^wikimedia:/i", $ex[4])) {
			    	$rtem = explode("wikimedia:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
				$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
				$wget = system('wget -O resulta.txt http://wikimediafoundation.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} else if (preg_match("/^incubator:/i", $ex[4])) {
			    	$rtem = explode("incubator:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
				$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
				$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$wget = system('wget -O resulta.txt http://incubator.wikimedia.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} elseif (preg_match("/^mw:/i", $ex[4])) {
			    	$rtem = explode("mw:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
				$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
				$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$wget = system('wget -O resulta.txt http://www.mediawiki.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				} else if (preg_match("/^es:/i", $ex[4])) {
			    	$rtem = explode("es:",$rparams);
			    	$rtem = str_replace("(", "\(", $rtem);
			    	$rtem = str_replace(")", "\)", $rtem);
				$rparams = ($rtem[1]) ? $rtem[1] : $mask[0];
				$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$wget = system('wget -O resulta.txt http://es.wikipedia.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				}  else {
				$rparams = ($rparams) ? $rparams : $mask[0];
			    	$rparams = str_replace("(", "\(", $rparams);
			    	$rparams = str_replace(")", "\)", $rparams);
			    	$rparams_e = nick_exists_udb($rparams);
			    	if ($rparams_e != false) $rparams = $rparams_e;
				$wget = system('wget -O resulta.txt http://es.wikipedia.org/w/api.php?action=query\&list=users\&ususers='.$rparams.'\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm',$wgeto);
				}
				parseinfo:
				$lines = file('resulta.txt');
				//Comprobamos si existe
				$tmpcheck = preg_replace("/&quot;/", "", $lines[21]);
				$tmpcheck = str_replace("\t", "", $tmpcheck);
				$tmpcheck = explode(" ", $tmpcheck);
				if ($tmpcheck[0] == "missing:") {
					fputs($socket, "PRIVMSG ".$ex[2]." :¡Error! El usuario ".chr(2).$rparams.chr(2)." no existe.\n");
				} else {
					//Leemos línea a línea
					$count = 0;
					foreach($lines as $linea) {
						//$linea = $lines($count);
						//Limpiamos los &quot;
						$linea = preg_replace("/&quot;/", "", $linea);
						//Limpiamos el tab
						$linea = str_replace("\t", "", $linea);
						//Si hay coma al final, la borramos
						$linea = preg_replace("/,$/", "", $linea);
						//Limpiamos saltos de línea
						$linea = str_replace("\n", "", $linea);
						//Ahora, actuamos según lo que haya en la primera parte
						$linea = explode(' ', $linea);
						$seccion = $linea[0];
						//Quitamos coma de la sección, si hay (grupos)
						$seccion = preg_replace("/,$/", "", $seccion);
						@ $valor = $linea[1];
						//Grupos
						//$grupos = array();
						switch ($seccion) {
							case "userid:":
							        $userid = $valor;
								break;
							case "name:":
								//Lo cojemos todo
								$valor = array_splice($linea, 1);
								$valor = implode(' ', $valor);
								$nombre = $valor;
								break;
							case "editcount:":
								 $ediciones = $valor;
								break;
							case "registration:":
								if ($valor == "null") {
								$registro = chr(3)."04N/D".chr(15);
								break;
								} else {
								$tmp = explode('T', $valor);
								$fechat = $tmp[0];
								$fechaw = explode('-', $fechat);
								$tmp2 = explode('Z', $tmp[1]);
								$horat = $tmp2[0];
								$registro = "El ".$fechaw[2]." de ".date( 'F', mktime(0, 0, 0, $fechaw[1], 1))." del ".$fechaw[0].", a las ".$horat.".";
								$registro = str_replace("January", "enero", $registro);
								$registro = str_replace("February", "febrero", $registro);
								$registro = str_replace("March", "marzo", $registro);
								$registro = str_replace("April", "abril", $registro);
								$registro = str_replace("May", "mayo", $registro);
								$registro = str_replace("June", "junio", $registro);
								$registro = str_replace("July", "julio", $registro);
								$registro = str_replace("August", "agosto", $registro);
								$registro = str_replace("September", "septiembre", $registro);
								$registro = str_replace("October", "octubre", $registro);
								$registro = str_replace("November", "noviembre", $registro);
								$registro = str_replace("December", "diciembre", $registro);
								break;
								}
							case "gender:":
								if ($valor == "male") {
									$igener = "M";
									$genero = chr(3)."12M-♂".chr(15);
								} elseif ($valor == "female") {
									$igener = "F";
									$genero = chr(3)."13F-♀".chr(15);
								} else {
									$igener = "M";
									$genero = chr(3)."04N/D".chr(15);
								}
								break;
							case "user":
								$fw = fopen("grupos.txt", 'ab');
								fwrite($fw, "usuario,");
								fclose($fw);
								break;
							case "autoconfirmed":
								$fw = fopen("grupos.txt", 'ab');
								fwrite($fw, " autoconfirmado,");
								fclose($fw);
								break;
							case "bureaucrat":
								$fw = fopen("grupos.txt", 'ab');
								fwrite($fw, chr(2)." burócrata".chr(2).",");
								fclose($fw);
								break;
							case "sysop":
								$fw = fopen("grupos.txt", 'ab');
								fwrite($fw, chr(2)." bibliotecario".chr(2).",");
								fclose($fw);
								break;
							case "autopatrolled":
								$fw = fopen("grupos.txt", 'ab');
								fwrite($fw, chr(2)." autoverificado".chr(2).",");
								fclose($fw);
								break;
							case "bot":
								$fw = fopen("grupos.txt", 'ab');
								fwrite($fw, chr(2)." bot".chr(2).",");
								fclose($fw);
								break;
							case "checkuser":
								$fw = fopen("grupos.txt", 'ab');
								fwrite($fw, chr(2)." checkuser".chr(2).",");
								fclose($fw);
								break;
							case "oversight":
								$fw = fopen("grupos.txt", 'ab');
								fwrite($fw, chr(2)." supresor".chr(2).",");
								fclose($fw);
								break;
							case "patroller":
								$fw = fopen("grupos.txt", 'ab');
								fwrite($fw, chr(2)." verificador".chr(2).",");
								fclose($fw);
								break;
							case "rollbacker":
								$fw = fopen("grupos.txt", 'ab');
								fwrite($fw, chr(2)." reversor".chr(2).",");
								fclose($fw);
								break;
							case "steward":
								$fw = fopen("grupos.txt", 'ab');
								fwrite($fw, chr(2).chr(3)."12 steward".chr(15).",");
								fclose($fw);
								break;
							case "</pre>":
								//Leemos los grupos
								$fw = fopen("grupos.txt", "rb");
								$gup = fgets($fw);
								$gup = preg_replace("/,$/", ".", $gup);
								fclose($fw);
								system("rm grupos.txt");
								if ($igener == "M") {
									fputs($socket, "PRIVMSG ".$ex[2]." :Usuario es ".chr(2).$nombre.chr(2)." (id: ".chr(2).$userid.chr(2)."). Ediciones: ".chr(2).$ediciones.chr(2).". Registrado: ".$registro." Género: ".$genero.". Grupos: ".$gup."\n");
								} elseif ($igener == "F") {
									fputs($socket, "PRIVMSG ".$ex[2]." :Usuaria es ".chr(2).$nombre.chr(2)." (id: ".chr(2).$userid.chr(2)."). Ediciones: ".chr(2).$ediciones.chr(2).". Registrada: ".$registro.". Género: ".$genero.". Grupos: ".$gup."\n");
								}
								//TODO: Nicks asociados: $u_nicks DONE
								
								//Creamos $p_nombre con los paréntesis aptos para parseo.
								$p_nombre = p_chars( $nombre );
								$u_nicks = array();
								$un = new UDB_Connector();
								$un->connect("nas.udb");
								$unl = file("cache.ucb");
								foreach ($unl as $uline) {
									$expl = explode("~!", trim($uline));
									if ($expl[0] == $p_nombre) {
										array_shift($expl);
										foreach ($expl as $expln) {
											array_push($u_nicks, $expln);
										}
									}
								}
								if ($u_nicks) { 
									$smoc = implode(", ",$u_nicks);
								} else {
									$smoc = "N/A";
								}
								// Ponemos bien los paréntesis en $smoc
								$smoc = dp_chars($smoc);
								fputs($socket, "PRIVMSG ".$ex[2]." :Nicks asociados: ".chr(2).$smoc.chr(2).".\n");
								break;
							}

						}
					}
					
				}
			}
		}
	}
	
// Funciones

/* nick_exists_udb

	@params $nick string
	return mixed
	
	Comprueba si un nick se nombra en la UDB, tanto como cuenta como asignado. Si no lo está, manda false. Si lo está, devuelve el nombre de la cuenta.
*/
function nick_exists_udb( $nick ) {
	// Abrimos la conexión con la UNDB
	$un = new UDB_Connector();
	$un_c = $un->connect("nas.udb");
	if ($un_c == false) goto end;
	
	// Actuamos sobre el cache 
	$udb_nas = fopen("cache.ucb", "r");
	$nas_found = false;
	while (!feof($udb_nas)) {
		$nas_line = fgets($udb_nas);
		$nas_expl = explode('~!', $nas_line);
		foreach ($nas_expl as $nas_vnick) {
			if (trim($nas_vnick) == $nick) { 
				$nas_found = true;
				$nas_aname = $nas_expl[0];
			}
		}
	}
	if ($nas_found != true) { 
		return false;
	} else {
		return $nas_aname;
		unset($nas_found);
	}
	fclose($udb_nas);
	
	// Guardamos 
	$un->save("nas.udb");
	end:
}

// Función para eliminar carácteres 'raros'
function p_chars( $text ) {
	$m_text = str_replace("(", "\(", $text);
	$m_text = str_replace(")", "\)", $m_text);
	return trim($m_text);
}

// Función para descodificar p_chars
function dp_chars( $text ) {
	$m_text = str_replace("\(", "(", $text);
	$m_text = str_replace("\)", ")", $m_text);
	return trim($m_text);
}

?>
