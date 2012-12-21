#!/usr/bin/python
# -*- coding: utf-8 -*-

#################
# UnBot IRC Bot #
#################

# Version: Shiva.2012.12.18b
# Author: Catbuntu

import socket
 
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

###################
#    Funciones    #
###################

def privmsg(target, text):
   irc.send ("PRIVMSG " + target + " :" + text + "\r\n")
def sc (cmd):
   irc.send (cmd + "\r\n")
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
      
      
   print data
