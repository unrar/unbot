#!/usr/bin/python
# -*- coding: utf-8 -*-

#################
# UnBot IRC Bot #
#################

# Version: Shiva 13.04 Beta
# Author: Catbuntu

import socket, re, os, urllib, time
from udb_connector import UNDB_Connector
#############
#  CONFIGS  #
#############

# Red
network = 'irc.freenode.net'
# Puerto
port = 6667
# Nick
nick = 'unabota'
# Realname
realname = 'Shiva 13.04 Beta'
# Ident
ident = 'shiva'
# Contraseña de nickserv
npass = 'openaccess'
# Canal principal
chan = '#wikipedia-es'
# Canales a entrar
chans = ["#sandyd", "#undb", "#undb-es", "#wikipedia-es-bots", "##bots-debug", "##wikicafe"]
# Dueño
owner = "wikimedia/unrar"
# Mensaje de error de permisos
perror = "¿Pero quién eres? ¡No estás autorizado a usar este comando!"
# Prefijos y sus respectivas URLS base
projects = {'wikt': 'https://es.wiktionary.org', 'b': 'https://es.wikibooks.org', 'n': 'https://es.wikinews.org', 'commons': 'https://commons.wikimedia.org', 'meta': 'https://meta.wikimedia.org', 'q': 'https://es.wikiquote.org', 's': 'https://es.wikisource.org', 'v': 'https://es.wikiversity.org', 'species': 'https://es.wikispecies.org', 'wikimedia': 'https://wikimediafoundation.org', 'incubator': 'https://incubator.wikimedia.org', 'mw': 'https://mediawiki.org'}
# Códigos de lenguaje
langs = ['es', 'en', 'ca']
# Idioma por defecto
deflang = 'es'
# Nombres de los meses
mname = {'01': 'enero', '02': 'febrero', '03': 'marzo', '04': 'abril', '05': 'mayo', '06': 'junio', '07': 'julio', '08': 'agosto', '09': 'septiembre', '10': 'octubre', '11': 'noviembre', '12': 'diciembre'}
# Descripcion de los comandos
descs = {'join': 'Entra a un canal. Solo para administradores.', 'part': 'Sale de un canal. Solo para administradores.', 'ping': 'Te responde con pong.', 'nas': 'Sin parámetros, te muestra los nicks asignados a tu cuenta. Si especificas una cuenta como parámetro, añade tu nick a ella (si no está asignado ya a otra).', 'ip': 'Muestra información sobre una IP.', 'gatos': 'Muestra las categorías de un artículo.', 'cats': 'Muestra las categorías de un artículo.', 'awiki': 'Muestra la URL de un enlace wiki.', 'info': 'Con parámetros, muestra información del nick dado. Sin parámetros, muestra la de tu nick. Si el nick tiene una cuenta asociada, muestra su información.', 'seen': 'Muestra datos (máscara, fecha, hora y canal) sobre la vez que el bot vio al nick dado entrar a un canal o hablar.', 'quit': 'Sale del IRC. Sólo para administradores.'}
# Uso de los comandos
usos = {'join': '&join <#canal>', 'part': '&part <#canal>', 'ping': '&ping', 'nas': '&nas [cuenta]', 'awiki': '&awiki [idioma:][proyecto:]<artículo>', 'cats': '&cats [idioma:][proyecto:]<artículo>', 'gatos': '&gatos [idioma:][proyecto:]<artículo>', 'info': '&info [[idioma:][proyecto:]<nick>]', 'ip': '&ip xxx.xxx.xxx.xxx', 'seen': '&seen', 'quit': '&quit'}
###################
#    Funciones    #
###################

def privmsg(target, text):
   irc.send ("PRIVMSG " + target + " :" + text + "\r\n")
def sc (cmd):
   irc.send (cmd + "\r\n")
def nick_exists_udb(nickn):
   un = UNDB_Connector()
   un_c = un.connect("nas.udb")
   if (un_c == True):
      udb_nas = open("cache.ucb", 'rb')
      nas_found = False
      for line in udb_nas:
         nas_expl = line.split("~!")
         for nas_vnick in nas_expl:
            if (nas_vnick.rstrip().lower() == nickn.lower()):
               nas_found = True
               nas_aname = nas_expl[0]
      if nas_found != True:
         return False
      else:
         return nas_aname
      un.save("nas.udb")
def unscape(stri):
  return re.sub(r'\\(.)', r'\1', stri)
  

def unescape(text):
    def fixup(m):
        text = m.group(0)
        if text[:2] == "&#":
            # character reference
            try:
                if text[:3] == "&#x":
                    return unichr(int(text[3:-1], 16))
                else:
                    return unichr(int(text[2:-1]))
            except ValueError:
                pass
        else:
            # named entity
            try:
                text = unichr(htmlentitydefs.name2codepoint[text[1:-1]])
            except KeyError:
                pass
        return text # leave as is
    return re.sub("&#?\w+;", fixup, text)
      
# Función para convertir un enlace wiki a URL
def c_url (wlink):
   lpr = deflang
   for lp in langs:
      if re.search('^' + lp + ':', wlink):
         lpr = lp
   wlink = re.sub(r'^' + lpr + ':', '', wlink)
   for prefix, url in projects.items():
      if re.search(r'^' + prefix + ':', wlink):
         return re.sub(r'^https?://es', 'https://' + lpr, url) + "/wiki/" + re.sub(r'%20', '_', urllib.quote(re.sub(r'^' + prefix + ':', '', wlink)))
   return re.sub(r'^https?://es', 'https://' + lpr, "https://es.wikipedia.org") + "/wiki/" + re.sub(r'%20', '_', urllib.quote(wlink))

# Función para dar la URL de la API
def cat_url (wlink):
   lpr = deflang
   for lp in langs:
      if re.search('^' + lp + ':', wlink):
         lpr = lp
   wlink = re.sub(r'^' + lpr + ':', '', wlink)
   for prefix, url in projects.items():
      if re.search(r'^' + prefix + ':', wlink):
         return re.sub(r'^https?://es', 'https://' + lpr, url) + "/w/api.php?action=query\&prop=categories\&titles=" + re.sub(r'%20', '_', urllib.quote(re.sub(r'^' + prefix + ':', '', wlink)))
   return re.sub(r'^https?://es', 'https://' + lpr, "https://es.wikipedia.org") + "/w/api.php?action=query\&prop=categories\&titles=" + re.sub(r'%20', '_', urllib.quote(wlink))

# Función para dar la URL de la API &info
def info_url (wlink):
   lpr = deflang
   for lp in langs:
      if re.search('^' + lp + ':', wlink):
         lpr = lp
   wlink = re.sub(r'^' + lpr + ':', '', wlink)
   for prefix, url in projects.items():
      if re.search(r'^' + prefix + ':', wlink):
         return re.sub(r'^https?://es', 'https://' + lpr, url) + "/w/api.php?action=query\&list=users\&ususers=" + re.sub(r'%20', '_', urllib.quote(re.sub(r'^' + prefix + ':', '', wlink))) + "\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm"
   return re.sub(r'^https?://es', 'https://' + lpr, "https://es.wikipedia.org") + "/w/api.php?action=query\&list=users\&ususers=" + re.sub(r'%20', '_', urllib.quote(wlink)) + "\&usprop=blockinfo\|groups\|editcount\|registration\|emailable\|gender\&format=jsonfm"

# Función para pasar de número a nombre de mes
def month_letter(month):
   for number, letter in mname.items():
      if number == month:
         return letter

# Función para encontrar el "last seen"
def useen(nickname):
   un = UNDB_Connector()
   un_c = un.connect("seen.udb")
   if (un_c == True):
      udb_s = open("seen.udb", 'rb')
      s_found = False
      s_cfound = False
      for line in udb_s:
         s_expl = line.split("%%!%%")
         if s_expl[0].rstrip().lower() == nickname.rstrip().lower():
            s_mask = s_expl[1]
            s_time = s_expl[2]
            s_chan = s_expl[3]
            s_found = True
      if s_found != True:
         return False
      else:
         return [s_mask, s_time, s_chan]
      un.save("seen.udb")
         
# Creamos el socket "irc"
irc = socket.socket ( socket.AF_INET, socket.SOCK_STREAM )
# Lo conectamos
irc.connect ( ( network, port ) )
print irc.recv ( 4096 )
irc.send ( 'NICK ' + nick + '\r\n' )
irc.send ( 'USER ' + ident + ' bla bla :' + realname + '\r\n' )
irc.send ( 'PRIVMSG NickServ :IDENTIFY ' + npass + '\r\n')
irc.send ( 'JOIN ' + chan + '\r\n' )
# Canales secundarios
for tcanal in chans:
   irc.send("JOIN " + tcanal + "\r\n")

while True:
   data = irc.recv ( 4096 )
   command = data.replace(chr(10), '')
   command = command.replace(chr(13), '')
   ex = command.split()
   # achan => actual channel
   try:
      achan = ex[2]
   except IndexError:
      pass
   params = ex[4:]
   rparams = ' '.join(params)
   if ex[0] == "PING":
	   irc.send ("PONG " + ex[1] + "\r\n")
   
   # On Join, para &seen
   if ex[1] == "JOIN":
      imask = ex[0].split("!")[0]
      inick = imask[1:]
      UC = UNDB_Connector()
      UC.connect("seen.udb")
      found = False
      file = open("cache.ucb", 'r')
      newfile = []
      for line in file:
         if line.split('%%!%%')[0].lower() == re.escape(inick).rstrip().lower():
            line = inick + "%%!%%" + ex[0][1:] + "%%!%%" + time.asctime( time.localtime(time.time()) ) + "%%!%%" + ex[2]
            found = True
         newfile.append(line.rstrip() + "\n")
      if found == False:
         nline = inick + "%%!%%" + ex[0][1:] + "%%!%%" + time.asctime( time.localtime(time.time()) ) + "%%!%%" + ex[2] + "\n"
         newfile.append(nline)
      file.close()
      fole = open("cache.ucb", "w")
      fole.write("".join(newfile))
      fole.close()
      UC.save("seen.udb")
   if ex[1] == "PRIVMSG":
      masktemp = ex[0].replace(chr(58), '')
      # mask[0] => nick
      # mask[1] => ident@host
      mask = masktemp.split('!')
      # Independent cloak
      cloak = mask[1].split('@')
      cloak = cloak[1]
      print ("@Debug: Mask[0] => " + mask[0] + "\n")
      print ("@Debug: Mask[1] => " + mask[1] + "\n")
      
      # Queries
      if ex[2].lower() == nick.lower():
         locparams = ' '.join(params)
         tempsp = ex[3].split(':')
         tempsp = tempsp[1:]
         primera = ':'.join(tempsp)
      else:
         imask = ex[0].split("!")[0]
         inick = imask[1:]
         UC = UNDB_Connector()
         UC.connect("seen.udb")
         found = False
         file = open("cache.ucb", 'r')
         newfile = []
         for line in file:
            if line.split('%%!%%')[0].lower() == re.escape(inick).rstrip().lower():
               line = inick + "%%!%%" + ex[0][1:] + "%%!%%" + time.asctime( time.localtime(time.time()) ) + "%%!%%" + ex[2]
               found = True
            newfile.append(line.rstrip() + "\n")
         if found == False:
            nline = inick + "%%!%%" + ex[0][1:] + "%%!%%" + time.asctime( time.localtime(time.time()) ) + "%%!%%" + ex[2] + "\n"
            newfile.append(nline)
         file.close()
         fole = open("cache.ucb", "w")
         fole.write("".join(newfile))
         fole.close()
         UC.save("seen.udb")
      # @Debug: Test command
      if ex[3].lower() == ":&ping":
         privmsg (achan, mask[0] + ", ¡PONG!")
      # Ayuda
      if (ex[3].lower() == ":&ayuda") or (ex[3].lower() == ":&help"):
         if len(ex) <= 4:
            privmsg(achan, "¡Hola! Soy el robot " + chr(3) + "05" + nick + chr(15) + ". Mis comandos (prefijados con \"&\") son: " + chr(3) + "12info, gatos (cats), awiki, join, part, quit, ping, ip, nas.");
            privmsg(achan, "Para más información, pon: " + chr(3) + "12&ayuda " + chr(2) + "elcomando" + chr(15) + ".")
         else:
            found = False
            for command, description in descs.items():
               if command == ex[4]:
                  privmsg(achan, "Descripción del comando: " + description)
                  privmsg(achan, "Uso del comando: " + chr(3) + "12" + usos[ex[4]])
                  found = True
            if found == False:
               privmsg(achan, "Lo siento, el comando " + chr(3) + "04" + ex[4] + chr(15) + " no consta en mi base de datos.")
      # Quit command for shiva
      if (' '.join(ex[3:5]).lower() == ':%shiva quit') or (ex[3] == ":&quit"):
         if cloak.find(owner) != -1:
            privmsg (achan, "OK, habrá que irse...")
            sc ( 'QUIT :I don\'t like to live on a yellow submarine...' )
         else:
            privmsg (achan, perror)
      # Join command
      if ex[3].lower() == ":&join":
         if cloak.find(owner) != -1:
            privmsg (achan, "Entrando a " + ex[4] + "...")
            sc("JOIN " + ex[4])
         else:
            privmsg (achan, perror)
      
      # Part command
      if ex[3].lower() == ":&part":
         if cloak.find(owner) != -1:
            privmsg (achan, "Saliendo de " + ex[4] + "...")
            sc("PART " + ex[4] + " :Part ordenado por " + mask[0])
         else:
            privmsg (achan, perror)
      
      # seen
      if (ex[3].lower() == ":&seen"):
         if len(ex) <= 4:
            privmsg(achan, chr(2) + mask[0] + chr(2) + ", ¡la sintaxis del comando es " + chr(3) + "12&seen nick" + chr(3) + "!")
         else:
            vseen = useen(ex[4].rstrip())
            if vseen == False:
               privmsg(achan, "El usuario no se ha podido encontrar en la base de datos.")
            else:
               smask = vseen[0]
               stime = vseen[1]
               schan = vseen[2]
               privmsg(achan, "El usuario " + chr(2) + ex[4] + chr(2) + " fue visto por última vez el " + chr(3) + "12" + stime + " (GMT)" + chr(15) + " en el canal " + chr(3) + "12" + schan.rstrip() + chr(15) + ", bajo la máscara " + chr(3) + "12" + smask + chr(15) + ".")
         
      if (ex[3].lower() == ":&cats") or (ex[3].lower() == ":&gatos"):
         if len(ex) <= 4:
            privmsg(achan, chr(2) + mask[0] + chr(2) + ", ¡la sintaxis del comando es " + chr(3) + "12&cats [idioma:][proyecto:]artículo" + chr(3) + "!")
         else:
            wget = os.system('wget -O resulta.txt ' + cat_url(' '.join(ex[4:])))
            flines = open('resulta.txt')
            lines = flines.readlines()
            tmpcheck = lines[20].replace('&quot;', '')
            tmpcheck = tmpcheck.replace("\t", "")
            tmpcheck = tmpcheck.replace("title=\"", "")
            tmpcheck = tmpcheck.replace("&lt;", "")
            tmpcheck = tmpcheck.replace("/&gt;", "")
            if tmpcheck.find("missing=") != -1:
               privmsg(achan, "¡Error! El artículo " + chr(2) + ' '.join(ex[4:]) + chr(2) + " no existe.")
            else:
               cats = []
               for linea in lines:
                  linea = linea.replace("&quot;", "")
                  linea = linea.replace("\t", "")
                  linea = re.sub(r",$", "", linea)
                  linea = linea.replace("\n", "")
                  linea = linea.replace("title=\"", "")
                  linea = linea.replace("&lt;", "")
                  linea = linea.replace("&gt;", "")
                  linea = linea.replace("\/&gt;", "")
                  linea = linea.replace("<span style=\"color:blue;\">cl ns=14 title=", "")
                  linea = linea.replace("</span>", "")
                  if (linea.find("Categoría") != -1) or (linea.find("Category") != -1):
                     cat = linea.split(":")
                     cat = cat[1:]
                     cat = ':'.join(cat)
                     cat = re.sub(r"/$", "", cat)
                     cats.append(cat)
               cats = ','.join(cats)
               cats = re.sub(r" ,", ", ", cats)
               cats = re.sub(r" $", ".", cats)
               vmsg = "Categorías de " + chr(2) + ' '.join(ex[4:]) + chr(15) + ": " + cats
               vmsg = vmsg.replace("Ã", "í")
               if len(vmsg) >= 373:
                  resto = len(vmsg) - 370
                  wmsg = vmsg[-resto:] + "\n"
                  vmsg = vmsg[:370] + chr(2) + chr(3) + "12..." + chr(15) + "\n"
                  if len(wmsg) >= 373:
                     restox = len(wmsg) - 370
                     xmsg = wmsg[-restox:] + "\n"
                     wmsg = wmsg[0:370] + chr(2) + chr(3) + "12..." + chr(15) + "\n"
                  else:
                     wmsg = wmsg + "\n"
               else:
                  vmsg = vmsg + "\n"
               privmsg(achan, vmsg)
               try:
                  wmsg
               except NameError:
                  wmsg = None
               if wmsg != None:
                  privmsg(achan, wmsg)
                  del wmsg
               try:
                  xmsg
               except NameError:
                  xmsg = None
               if xmsg != None:
                  privmsg(achan, xmsg)
                  del xmsg
               privmsg(achan, chr(3) + "12[1] " + c_url(' '.join(ex[4:])) + chr(15))
      if ex[3].lower() == ":&info":
         try:
            ex[4]
         except IndexError:
            ex.append(None)
         if ex[4] != None:
            if (re.sub(r":$", "", ex[4]) in projects) or (re.sub(r":$", "", ex[4]) in langs):
               privmsg (achan, "Lo siento, pero esta función aún no está soportada. Se mostrará la información local de tu nick.")
               ex[4] = mask[0]
            partes = ex[4:]
            nnick = []
            enick = []
            for parte in partes:
               nnick.append(parte)
               enick.append(re.escape(parte))
            np = '_'.join(nnick)
            ne = '_'.join(enick)
         else:
            np = mask[0]
            ne = re.escape(mask[0])
            
         # np = nick (no escapado) a buscar
         # ne = nick escapado
         # comprobamos si está en la undb
         np_e = nick_exists_udb(ne)
         if np_e != False:
            np = unscape(np_e)
         
         # Ahora, si existe, np será el nick
         # Descargamos la api usando la función
         wget = os.system('wget -O resulta.txt ' + info_url(np))
         
         # Abrimos el fichero
         
         flines = open('resulta.txt')
         lines = flines.readlines()
         tmpcheck = lines[21].replace("&quot;", "")
         tmpcheck = tmpcheck.replace("\t", "")
         tmpcheck = tmpcheck.split(" ")
         if tmpcheck[0] == "missing:":
            privmsg(achan, "¡Error! El usuario " + chr(2) + np + chr(2) + " no existe.")
         else:
            for linea in lines:
               linea = linea.replace("&quot;", "")
               linea = linea.replace("\t", "")
               linea = re.sub(r",$", "", linea)
               linea = linea.replace("\n", "")
               linea = linea.split(" ")
               seccion = linea[0]
               seccion = re.sub(r",$", "", seccion)
               try:
                  linea[1]
               except IndexError:
                  linea.append(None)
               if linea[1] != None:
                  valor = linea[1]
               
               # Switch
               if seccion == "userid:":
                  userid = valor
               elif seccion == "name:":
                  valor = linea[1:]
                  valor = ' '.join(valor)
                  nombre = valor
               elif seccion == "editcount:":
                  ediciones = valor
               elif seccion == "registration:":
                  if valor == "null":
                     registro = chr(3) + "04N/D" + chr(15)
                  else:
                     tmp = valor.split('T')
                     fechat = tmp[0]
                     fechaw = fechat.split('-')
                     tmp2 = tmp[1].split("Z")
                     horat = tmp2[0]
                     registro = "El " + fechaw[2] + " de " + month_letter(fechaw[1]) + " del " + fechaw[0] + ", a las " + horat + "."
               elif seccion == "gender:":
                  if valor == "male":
                     igener = "M"
                     genero = chr(3) + "12M-♂" + chr(15)
                  elif valor == "female":
                     igener = "F"
                     genero = chr(3) + "13F-♀" + chr(15)
                  else:
                     igener = "M"
                     genero = chr(3) + "04N/D" + chr(15)
               elif seccion == "user":
                  fw = open("grupos.txt", 'ab')
                  fw.write(" usuario,")
                  fw.close()
               elif seccion == "autoconfirmed":
                  fw = open("grupos.txt", 'ab')
                  fw.write(" autoconfirmado,")
                  fw.close()
               elif seccion == "bureaucrat":
                  fw = open("grupos.txt", 'ab')
                  fw.write(chr(2) + " burócrata" + chr(2) + ",")
                  fw.close()
               elif seccion == "sysop":
                  fw = open("grupos.txt", 'ab')
                  fw.write(chr(2) + " bibliotecario" + chr(2) + ",")
                  fw.close()
               elif seccion == "autopatrolled":
                  fw = open("grupos.txt", 'ab')
                  fw.write(chr(2) + " autoverificado" + chr(2) + ",")
                  fw.close()
               elif seccion == "bot":
                  fw = open("grupos.txt", 'ab')
                  fw.write(chr(2) + " bot" + chr(2) + ",")
                  fw.close()
               elif seccion == "checkuser":
                  fw = open("grupos.txt", 'ab')
                  fw.write(chr(2) + " checkuser" + chr(2) + ",")
                  fw.close()
               elif seccion == "oversight":
                  fw = open("grupos.txt", 'ab')
                  fw.write(chr(2) + " supresor" + chr(2) + ",")
                  fw.close()
               elif seccion == "patroller":
                  fw = open("grupos.txt", 'ab')
                  fw.write(chr(2) + " verificador" + chr(2) + ",")
                  fw.close()
               elif seccion == "rollbacker":
                  fw = open("grupos.txt", 'ab')
                  fw.write(chr(2) + " reversor" + chr(2) + ",")
                  fw.close()
               elif seccion == "steward":
                  fw = open("grupos.txt", 'ab')
                  fw.write(chr(2) + chr(3) + "12 steward" + chr(15) + ",")
                  fw.close()
               elif seccion == "</pre>":
                  try:
                     fw = open("grupos.txt", "rb")
                     gup = fw.read()
                     gup = re.sub(r",$", ".", gup)
                     fw.close()
                     os.system("rm grupos.txt")
                     if igener == "M":
                        privmsg(achan, "Usuario es " + chr(2) + nombre + chr(2) + " (id: " + chr(2) + userid + chr(2) + "). Ediciones: " + chr(2) + ediciones + chr(2) + ". Registrado: " + registro + " Género: " + genero + ". Grupos:" + gup);
                     elif igener == "F":
                        privmsg(achan, "Usuaria es " + chr(2) + nombre + chr(2) + " (id: " + chr(2) + userid + chr(2) + "). Ediciones: " + chr(2) + ediciones + chr(2) + ". Registrada: " + registro + " Género: " + genero + ". Grupos:" + gup);
                  
                     # Nicks asociados
                     p_nombre = re.escape(nombre)
                     u_nicks = []
                     un = UNDB_Connector()
                     un.connect("nas.udb")
                     unl = open("cache.ucb")
                     for uline in unl:
                        expl = uline.rstrip().split("~!")
                        if expl[0] == p_nombre:
                           print "Found: " + expl[0] + "\n"
                           expl.pop(0)
                           for expln in expl:
                              print "Found nick: " + expln + "\n"
                              u_nicks.append(expln)
                     try:
                        u_nicks[0]
                     except IndexError:
                        u_nicks = None
                     if u_nicks != None:
                        smoc = ', '.join(u_nicks)
                     else:
                        smoc = "N/A"
                     smoc = re.sub(r'\\(.)', r'\1', smoc)
                     privmsg(achan, "Nicks asociados: " + chr(2) + smoc + chr(2) + ".")
                  except:
                     privmsg(achan, "Hubo un error al procesar tu solicitud. Porfavor, comprueba &ayuda info.")
      if (ex[3].lower() == ":&nas") or (ex[3].lower() == ":&nariz"):
         if len(ex) >= 5:
            # There's an argument
            udb_ne = nick_exists_udb(re.escape(mask[0]))
            if udb_ne != False:
               privmsg(achan, "Lo siento, el nick " + chr(3) + "12" + mask[0] + chr(15) + " ya está asignado a una cuenta.")
            else :
               uu = UNDB_Connector()
               uu_cc = uu.connect("nas.udb")
               if uu_cc == True:
                  found = False
                  file = open("cache.ucb", 'r')
                  newfile = []
                  for line in file:
                     if line.split('~!')[0].lower() == re.escape(ex[4]).rstrip().lower():
                        line =  line.rstrip() + "~!" + mask[0]
                        found = True
                     newfile.append(line.rstrip() + "\n")
                  if found == False:
                     nline = re.escape(ex[4]).rstrip() + "~!" + mask[0] + "\n"
                     newfile.append(nline)
                  file.close()
                  fole = open("cache.ucb", "w")
                  fole.write("".join(newfile))
                  fole.close()
                  privmsg(achan, "Asociado correctamente tu nick " + chr(3) + "12" + mask[0] + chr(15) + " a la cuenta " + ex[4] + ".")
                  uu.save("nas.udb")
         else:
            # There's no argument
            u = UNDB_Connector()
            u_cc = u.connect("nas.udb")
            if u_cc == False:
               privmsg(achan, "Lo siento, el nick " + chr(3) + "12" + mask[0] + chr(15) + " ya está asignado a una cuenta.")
            else:
               udb_nas = open("cache.ucb", "r")
               nas_nicks = []
               nas_found = False
               for line in udb_nas:
                  nas_expl = line.split("~!")
                  for nas_vnick in nas_expl:
                     nas_vnick = unscape(nas_vnick)
                     if (nas_vnick.rstrip().lower() == mask[0].lower()): nas_found = True
                     nas_nicks.append(nas_vnick.rstrip())
                  if nas_found == True:
                     udb_dbo = nas_nicks.pop(0)
                     privmsg(achan, "Tu cuenta " + nas_expl[0] + " tiene los siguientes nicks asociados: " + chr(3) + "12" + ' , '.join(nas_nicks) + chr(15) + ".")
                     break 
                  nas_nicks = []
               if nas_found != True:
                  privmsg(achan, "Tu nick no está asociado a ninguna cuenta.")
               udb_nas.close()
               u.save("nas.udb")
      # awiki: Devuelve el enlace
      if ex[3].lower() == ':&awiki':
         if len(ex) <= 4:
            privmsg(achan, chr(2) + mask[0] + chr(2) + ", ¡la sintaxis del comando es " + chr(3) + "12&awiki [enlace wiki]" + chr(15) + "!")
         else:
            privmsg(achan, chr(3) + "12[1] " + c_url(' '.join(ex[4:])) + chr(15))
      if ex[3].lower() == ":&ip":
         # CheckSyntax
         if len(ex) <= 4:
            privmsg(achan, chr(2) + mask[0] + chr(2) + ", ¡la sintaxis del comando es " + chr(3) + "12&ip xxx.xxx.xxx.xxx" + chr(15) + "!")
         else:
            os.system("wget -O ip.txt http://whatismyipaddress.com/ip/" + ex[4].rstrip())
            lines = open("ip.txt", 'r')
            g_country = "N/A"
            g_host = "N/A"
            g_isp = "N/A"
            g_region = "N/A"
            g_city = "N/A"
            g_latitude = "N/A"
            g_longitude = "N/A"
            for linea in lines:
               # Country
               if linea.find("Country") != -1:
                  print "REACHED COUNTRY\n"
                  ter = linea.split("<")
                  pter = ter[4].split(">")
                  g_country = pter[1].replace(" ", "")
               #ISP and rDNS
               if linea.find("<!-- rDNS:") != -1:
                  print "REACHED RDNS\n"
                  ter = linea.split("rDNS: ")
                  pter = ter[1].split(" -->")
                  g_host = pter[0]
                  kter = linea.split("ISP:<")
                  qkter = kter[1].split("<")
                  pkter = qkter[1].split(">")
                  g_isp = pkter[1]
               # State and region
               if linea.lower().find("state/region") != -1:
                  ter = linea.split("<")
                  pter = ter[4].split(">")
                  g_region = pter[1]
               # City
               if linea.lower().find("city:") != -1:
                  ter = linea.split("<")
                  pter = ter[4].split(">")
                  g_city = pter[1]
               #Latitude
               if linea.lower().find("latitude:<") != -1:
                  ter = linea.split("<")
                  pter = ter[4].split(">")
                  g_latitude = pter[1]
               #Longitude
               if linea.lower().find("longitude:<") != -1:
                  ter = linea.split("<")
                  pter = ter[4].split(">")
                  g_longitude = pter[1]
               # End
               if linea.lower().find("</html>") != -1:
                  privmsg(achan, "Información de la IP " + chr(2) + ex[4] + chr(2) + ": Host: " + g_host.rstrip() + " - Localización: " + g_city.rstrip() + ", " + g_region.rstrip() + ", " + g_country.rstrip() + " - Coordenadas: " + g_latitude.rstrip() + ", " + g_longitude.rstrip() + " - ISP: " + g_isp.rstrip() + " - Bloquear: " + chr(3) + "10https://es.wikipedia.org/wiki/Especial:Bloquear/" + ex[4].replace(".", "%2E").rstrip() + chr(15) + " - Más información: " + chr(3) + "12http://www.whatismyipaddress.com/ip/" + ex[4].replace(".", "%2E").rstrip() + chr(15) + ".")
   print data
