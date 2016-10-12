#!/bin/sh

if [ -e /tmp/_sphinx_index_lock ] 
then
  exit
else
  touch /tmp/_sphinx_index_lock
  if [ -e /tmp/_sphinx_reindex_flag ] 
  then
    # full index
    /usr/bin/indexer messages --quiet --rotate
    rm /tmp/_sphinx_reindex_flag
  else
    # delta index
    /usr/bin/indexer delta --quiet --rotate
  fi
  rm /tmp/_sphinx_index_lock
fi
