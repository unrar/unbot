#!/usr/bin/python
# -*- coding: utf-8 -*-
import os.path

class UNDB_Connector:
   def __init__(self):
      self.cachedb = "cache.ucb"
   # rac: read-and-cache
   def rac(self, pdb):
      rpdb = open(pdb, 'rb')
      rcachedb = open(self.cachedb, 'wb')
      for line in rpdb:
         rcachedb.write(line)
      rpdb.close()
      rcachedb.close()
      return True
   
   # wac: write-and-cache
   def wac(self, pdb):
      rpdb = open(pdb, 'wb')
      rcachedb = open(self.cachedb, 'rb')
      for line in rcachedb:
         rpdb.write(line)
      rpdb.close()
      rcachedb.close()
      return True

   # save: alias for wac
   def save(self, pdb):
      self.wac(pdb)
      
   # connect: connect to an undb
   def connect(self, fdb, nocache = False):
      if os.path.isfile(fdb):
         self.rac(fdb)
         return True
      else:
         return False
