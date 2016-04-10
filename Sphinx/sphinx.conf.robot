# Sphinx configuration file sample
#
# WARNING! While this sample file mentions all available options,
# it contains (very) short helper descriptions only. Please refer to
# doc/sphinx.html for details.
#

#############################################################################
## data source definition
#############################################################################

source combined
{
	# data source type. mandatory, no default value
	# known types are mysql, pgsql, mssql, xmlpipe, xmlpipe2, odbc
	type			= xmlpipe2

	xmlpipe_command = /bin/cat /data/base/sphinx_xml/head.xml /data/base/sphinx_xml/2015*.xml /data/base/sphinx_xml/tail.xml
	xmlpipe_field_string = word
	xmlpipe_field_string = reply
}

# inherited source example
#
# all the parameters are copied from the parent source,
# and may then be overridden in this source definition
source combined_delta: combined
{
	xmlpipe_command = /data/app/php/bin/php /data/www/robot/sphinx/returnIncrementIndex.php
}

#############################################################################
## index definition
#############################################################################

# local index example
#
# this is an index which is stored locally in the filesystem
#
# all indexing-time options (such as morphology and charsets)
# are configured per local index
index combinedindex
{
	type			= plain
	source			= combined

	# index files path and file name, without extension
	# mandatory, path must be writable, extensions will be auto-appended
	path			= /data/app/sphinx/var/robot/combined

	# document attribute values (docinfo) storage mode
	# optional, default is 'extern'
	# known values are 'none', 'extern' and 'inline'
	docinfo			= extern

	# memory locking for cached data (.spa and .spi), to prevent swapping
	# optional, default is 0 (do not mlock)
	# requires searchd to be run from root
	mlock			= 0

	charset_type		= utf-8
	chinese_dictionary = /data/app/sphinx/etc/chinese-xdict
	# charset definition and case folding rules "table"
	# optional, default value depends on charset_type
	#
	# defaults are configured to include English and Russian characters only
	# you need to change the table to include additional ones
	# this behavior MAY change in future versions
	#
	# 'sbcs' default value is
	# charset_table		= 0..9, A..Z->a..z, _, a..z, U+A8->U+B8, U+B8, U+C0..U+DF->U+E0..U+FF, U+E0..U+FF
	#
	# 'utf-8' default value is
	# charset_table		= 0..9, A..Z->a..z, _, a..z, U+410..U+42F->U+430..U+44F, U+430..U+44F

	}


	# inherited index example
	#
	# all the parameters are copied from the parent index,
	# and may then be overridden in this index definition
	index combinedindex_delta: combinedindex
	{
	source			= combined_delta
	path			= /data/app/sphinx/var/robot/combined_delta
}


# distributed index example
#
# this is a virtual index which can NOT be directly indexed,
# and only contains references to other local and/or remote indexes
index dist1
{
	# 'distributed' index type MUST be specified
	type			= distributed

	# local index to be searched
	# there can be many local indexes configured
	local			= combinedindex
	local			= combinedindex_delta

	# remote agent
	# multiple remote agents may be specified
	# syntax for TCP connections is 'hostname:port:index1,[index2[,...]]'
	# syntax for local UNIX connections is '/path/to/socket:index1,[index2[,...]]'
	#agent			= localhost:9313:remote1
	#agent			= localhost:9314:remote2,remote3
	# agent			= /var/run/searchd.sock:remote4

	# remote agent connection timeout, milliseconds
	# optional, default is 1000 ms, ie. 1 sec
	agent_connect_timeout	= 1000

	# remote agent query timeout, milliseconds
	# optional, default is 3000 ms, ie. 3 sec
	agent_query_timeout	= 3000
}

#############################################################################
## indexer settings
#############################################################################

indexer
{
	# memory limit, in bytes, kiloytes (16384K) or megabytes (256M)
	# optional, default is 32M, max is 2047M, recommended is 256M to 1024M
	mem_limit		= 256M

	# write buffer size, bytes
	# several (currently up to 4) buffers will be allocated
	# write buffers are allocated in addition to mem_limit
	# optional, default is 1M
	#
	write_buffer		= 4M
}

#############################################################################
## searchd settings
#############################################################################

searchd
{
	# [hostname:]port[:protocol], or /unix/socket/path to listen on
	# known protocols are 'sphinx' (SphinxAPI) and 'mysql41' (SphinxQL)
	listen			= 127.0.0.1:9310

	# log file, searchd run info is logged here
	# optional, default is 'searchd.log'
	log			= /data/app/sphinx/log/searchd.log

	# query log file, all search queries are logged here
	# optional, default is empty (do not log queries)
	query_log		= /data/app/sphinx/log/query.log

	# client read timeout, seconds
	# optional, default is 5
	read_timeout		= 5

	# request timeout, seconds
	# optional, default is 5 minutes
	client_timeout		= 300

	# maximum amount of children to fork (concurrent searches to run)
	# optional, default is 0 (unlimited)
	max_children		= 30

	# PID file, searchd process ID file name
	# mandatory
	pid_file		= /data/app/sphinx/log/searchd.pid

	# max amount of matches the daemon ever keeps in RAM, per-index
	# WARNING, THERE'S ALSO PER-QUERY LIMIT, SEE SetLimits() API CALL
	# default is 1000 (just like Google)
	max_matches		= 1000

	# seamless rotate, prevents rotate stalls if precaching huge datasets
	# optional, default is 1
	seamless_rotate		= 1

	# whether to forcibly preopen all indexes on startup
	# optional, default is 1 (preopen everything)
	preopen_indexes		= 1

	# whether to unlink .old index copies on succesful rotation.
	# optional, default is 1 (do unlink)
	unlink_old		= 1

	# attribute updates periodic flush timeout, seconds
	# updates will be automatically dumped to disk this frequently
	# optional, default is 0 (disable periodic flush)
	#
	attr_flush_period	= 900

	# MVA updates pool size
	# shared between all instances of searchd, disables attr flushes!
	# optional, default size is 1M
	mva_updates_pool	= 1M

	# max allowed network packet size
	# limits both query packets from clients, and responses from agents
	# optional, default size is 8M
	max_packet_size		= 8M

	# max allowed per-query filter count
	# optional, default is 256
	max_filters		= 256

	# max allowed per-filter values count
	# optional, default is 4096
	max_filter_values	= 4096

	# socket listen queue length
	# optional, default is 5
	#
	listen_backlog		= 256

	# per-keyword read buffer size
	# optional, default is 256K
	#
	# read_buffer		= 256K

	# unhinted read size (currently used when reading hits)
	# optional, default is 32K
	#
	# read_unhinted		= 32K

	# max allowed per-batch query count (aka multi-query count)
	# optional, default is 32
	max_batch_queries	= 32

	# multi-processing mode (MPM)
	# known values are none, fork, prefork, and threads
	# optional, default is fork
	#
	workers			= fork

	# max threads to create for searching local parts of a distributed index
	# optional, default is 0, which means disable multi-threaded searching
	# should work with all MPMs (ie. does NOT require workers=threads)
	#
	# dist_threads		= 4
}