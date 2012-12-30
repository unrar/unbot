#!/usr/bin/python
# -*- coding: utf-8 -*-

#################
# UnBot IRC Bot #
#################

# Version: Shiva.2012.12.18b
# Author: Catbuntu

import socket, re, os, urllib
from udb_connector import UNDB_Connector
#############
#  CONFIGS  #
#############

# Red
network = 'irc.freenode.net'
# Puerto
port = 6667
# Nick
nick = 'unshiva'
# Realname
realname = 'Shiva 2012.12.18b'
# Ident
ident = 'shiva'
# Canal principal
chan = '##bots-debug'
# Canales a entrar
chans = ["#sandyd", "#undb", "#undb-es", "#wikipedia-es-bots"]
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
            if (nas_vnick.rstrip() == nickn):
               nas_found = True
               nas_aname = nas_expl[0]
      if nas_found != True:
         return False
      else:
         return nas_aname
      un.save("nas.udb")
def unscape(stri):
  return re.sub(r'\\(.)', r'\1', stri)
      
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

# Creamos el socket "irc"
irc = socket.socket ( socket.AF_INET, socket.SOCK_STREAM )
# Lo conectamos
irc.connect ( ( network, port ) )
print irc.recv ( 4096 )
irc.send ( 'NICK ' + nick + '\r\n' )
irc.send ( 'USER ' + ident + ' bla bla :' + realname + '\r\n' )
irc.send ( 'JOIN ' + chan + '\r\n' )
# Canales secundarios
for tcanal in chans:
   irc.send("JOIN " + tcanal + "\r\n")
irc.send ( 'PRIVMSG ' + chan + ' :Hola mundo.\r\n' )

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
         
      # @Debug: Test command
      if ex[3].lower() == ":&test":
         privmsg (achan, "Prueba recibida, gracias.")
         
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
      
      # Nas command, to manage associations

      
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
                     if line.split('~!')[0] == re.escape(ex[4]).rstrip():
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
                     if (nas_vnick.rstrip() == mask[0]): nas_found = True
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
