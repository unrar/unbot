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
# Canal
chan = '#wikipedia-es-bots'
##########################

# Creamos el socket "irc"
irc = socket.socket ( socket.AF_INET, socket.SOCK_STREAM )
# Lo conectamos
irc.connect ( ( network, port ) )
print irc.recv ( 4096 )
irc.send ( 'NICK ' + nick + '\r\n' )
irc.send ( 'USER ' + ident + ' bla bla :' + realname + '\r\n' )
irc.send ( 'JOIN ' + chan + '\r\n' )
irc.send ( 'PRIVMSG ' + chan + ' :Hola mundo.\r\n' )
while True:
   data = irc.recv ( 4096 )
   if data.find ( 'PING' ) != -1:
      irc.send ( 'PONG ' + data.split() [ 1 ] + '\r\n' )
   if data.find ( '%shiva quit' ) != -1:
      irc.send ( 'PRIVMSG ' + chan + " :Fine, if you don't want me\r\n" )
      irc.send ( 'QUIT\r\n' )
   if data.find ( 'hola shiva' ) != -1:
      irc.send ( 'PRIVMSG ' + chan + ' :Ya he dicho hola...\r\n' )
   if data.find ( 'hey shiva' ) != -1:
      irc.send ( 'PRIVMSG ' + chan + ' :Ya he dicho hola...\r\n' )
   if data.find ( 'KICK' ) != -1:
      irc.send ( 'JOIN ' + chan + '\r\n' )
   if data.find ( 'cheese' ) != -1:
      irc.send ( 'PRIVMSG ' + chan + ' :WHERE!!!!!!\r\n' )
   if data.find ( 'slaps shiva' ) != -1:
      irc.send ( 'PRIVMSG ' + chan + ' :This is the Trout Protection Agency. Please put the Trout Down and walk away with your hands in the air.\r\n' )
   print data
