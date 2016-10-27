<?php

namespace Tatikoma\React\Cassandra;

final class Constants
{
    /**
     * Connection status - not connected
     */
    const CLIENT_CLOSED = 1;
    /**
     * Connection status - during connecting
     */
    const CLIENT_CONNECTING = 2;
    /**
     * Connection status - connected
     */
    const CLIENT_CONNECTED = 4;
    /**
     * Connection status - closing connection
     */
    const CLIENT_CLOSING = 8;

    /**
     * Minimum frame size in bytes (frame header size)
     */
    const FRAME_SIZE_MIN = 0x09;
    /**
     * Currently a frame is limited to 256MB in length
     */
    const FRAME_SIZE_LIMIT = 0x10000000;
    /**
     * Frame direction flag - from client
     */
    const FRAME_DIRECTION_CLIENT = 0x00;
    /**
     * Frame direction flag - from server
     */
    const FRAME_DIRECTION_SERVER = 0x80;

    /**
     * Frame flag - compression
     */
    const FRAME_FLAG_COMPRESSION = 0x01;
    /**
     * Frame flag - tracing
     */
    const FRAME_FLAG_TRACING = 0x02;
    /**
     * Frame flag - custom payload
     */
    const FRAME_FLAG_PAYLOAD = 0x04;
    /**
     * Frame flag - warning
     */
    const FRAME_FLAG_WARNING = 0x08;

    /**
     * Frame opcode - Error
     */
    const OPCODE_ERROR = 0x00;
    /**
     * Frame opcode - Startup
     */
    const OPCODE_STARTUP = 0x01;
    /**
     * Frame opcode - Ready
     */
    const OPCODE_READY = 0x02;
    /**
     * Frame opcode - Authenticate
     */
    const OPCODE_AUTHENTICATE = 0x03;
    /**
     * Frame opcode - Options
     */
    const OPCODE_OPTIONS = 0x05;
    /**
     * Frame opcode - Supported
     */
    const OPCODE_SUPPORTED = 0x06;
    /**
     * Frame opcode - Query
     */
    const OPCODE_QUERY = 0x07;
    /**
     * Frame opcode - Result
     */
    const OPCODE_RESULT = 0x08;
    /**
     * Frame opcode - Prepare
     */
    const OPCODE_PREPARE = 0x09;
    /**
     * Frame opcode - Execute
     */
    const OPCODE_EXECUTE = 0x0A;
    /**
     * Frame opcode - Register
     */
    const OPCODE_REGISTER = 0x0B;
    /**
     * Frame opcode - Event
     */
    const OPCODE_EVENT = 0x0C;
    /**
     * Frame opcode - Batch
     */
    const OPCODE_BATCH = 0x0D;
    /**
     * Frame opcode - Auth Challenge
     */
    const OPCODE_AUTH_CHALLENGE = 0x0E;
    /**
     * Frame opcode - Auth Response
     */
    const OPCODE_AUTH_RESPONSE = 0x0F;
    /**
     * Frame opcode - Auth Success
     */
    const OPCODE_AUTH_SUCCESS = 0x10;

    /**
     * A consistency level specification.
     */
    const CONSISTENCY_ANY = 0x0000;
    /**
     * A consistency level specification.
     */
    const CONSISTENCY_ONE = 0x0001;
    /**
     * A consistency level specification.
     */
    const CONSISTENCY_TWO = 0x0002;
    /**
     * A consistency level specification.
     */
    const CONSISTENCY_THREE = 0x0003;
    /**
     * A consistency level specification.
     */
    const CONSISTENCY_QUORUM = 0x0004;
    /**
     * A consistency level specification.
     */
    const CONSISTENCY_ALL = 0x0005;
    /**
     * A consistency level specification.
     */
    const CONSISTENCY_LOCAL_QUORUM = 0x0006;
    /**
     * A consistency level specification.
     */
    const CONSISTENCY_EACH_QUORUM = 0x0007;
    /**
     * A consistency level specification.
     */
    const CONSISTENCY_SERIAL = 0x0008;
    /**
     * A consistency level specification.
     */
    const CONSISTENCY_LOCAL_SERIAL = 0x0009;
    /**
     * A consistency level specification.
     */
    const CONSISTENCY_LOCAL_ONE = 0x000A;

    /**
     * Values. If set, a [short] <n> followed by <n> [value]
     * values are provided. Those values are used for bound variables in
     * the query. Optionally, if the 0x40 flag is present, each value
     * will be preceded by a [string] name, representing the name of
     * the marker the value must be bound to.
     */
    const QUERY_FLAG_VALUES = 0x01;
    /**
     * Skip_metadata. If set, the Result Set returned as a response
     * to the query (if any) will have the NO_METADATA flag
     */
    const QUERY_FLAG_NO_METADATA = 0x02;
    /**
     * Page_size. If set, <result_page_size> is an [int]
     * controlling the desired page size of the result (in CQL3 rows).
     */
    const QUERY_FLAG_PAGE_SIZE = 0x04;
    /**
     * With_paging_state. If set, <paging_state> should be present.
     * <paging_state> is a [bytes] value that should have been returned
     * in a result set (Section 4.2.5.2). The query will be
     * executed but starting from a given paging state.
     */
    const QUERY_FLAG_WITH_PAGING_STATE = 0x08;
    /**
     * With serial consistency. If set, <serial_consistency> should be
     * present. <serial_consistency> is the [consistency] level for the
     * serial phase of conditional updates. That consitency can only be
     * either SERIAL or LOCAL_SERIAL and if not present, it defaults to
     * SERIAL. This option will be ignored for anything else other than a
     * conditional update/insert.
     */
    const QUERY_FLAG_WITH_SERIAL_CONSISTENCY = 0x10;
    /**
     * With default timestamp. If set, <timestamp> should be present.
     * <timestamp> is a [long] representing the default timestamp for the query
     * in microseconds (negative values are forbidden). This will
     * replace the server side assigned timestamp as default timestamp.
     * Note that a timestamp in the query itself will still override
     * this timestamp. This is entirely optional.
     */
    const QUERY_FLAG_WITH_DEFAULT_TIMESTAMP = 0x20;
    /**
     * With names for values. This only makes sense if the 0x01 flag is set and
     * is ignored otherwise. If present, the values from the 0x01 flag will
     * be preceded by a name (see above). Note that this is only useful for
     * QUERY requests where named bind markers are used; for EXECUTE statements,
     * since the names for the expected values was returned during preparation,
     * a client can always provide values in the right order without any names
     * and using this flag, while supported, is almost surely inefficient.
     */
    const QUERY_FLAG_WITH_NAMES_FOR_VALUES = 0x40;

    /**
     * for results carrying no information.
     */
    const RESULT_VOID = 0x01;
    /**
     * for results to select queries, returning a set of rows.
     */
    const RESULT_ROWS = 0x02;
    /**
     * the result to a `use` query.
     */
    const RESULT_SET_KEYSPACE = 0x03;
    /**
     * result to a PREPARE message.
     */
    const RESULT_PREPARED = 0x04;
    /**
     * the result to a schema altering query.
     */
    const RESULT_SCHEMA_CHANGE = 0x05;

    /**
     * if set, only one table spec (keyspace
     * and table name) is provided as <global_table_spec>. If not
     * set, <global_table_spec> is not present.
     */
    const RESULT_FLAG_GLOBAL_TABLE_SPEC = 0x0001;
    /**
     * Indicates whether this is not the last
     * page of results and more should be retrieved. If set, the
     * <paging_state> will be present. The <paging_state> is a
     * [bytes] value that should be used in QUERY/EXECUTE to
     * continue paging and retrieve the remainder of the result for
     * this query
     */
    const RESULT_FLAG_HAS_MORE_PAGES = 0x0002;
    /**
     *  if set, the <metadata> is only composed of
     * these <flags>, the <column_count> and optionally the
     * <paging_state> (depending on the Has_more_pages flag) but
     * no other information (so no <global_table_spec> nor <col_spec_i>).
     * This will only ever be the case if this was requested
     * during the query (see QUERY and RESULT messages).
     */
    const RESULT_FLAG_NO_METADATA = 0x0004;

    const FIELD_TYPE_CUSTOM = 0x0000;
    const FIELD_TYPE_ASCII = 0x0001;
    const FIELD_TYPE_BLOB = 0x0002;
    const FIELD_TYPE_BOOLEAN = 0x0004;
    const FIELD_TYPE_COUNTER = 0x0005;
    const FIELD_TYPE_DECIMAL = 0x0006;
    const FIELD_TYPE_DOUBLE = 0x0007;
    const FIELD_TYPE_FLOAT = 0x0008;
    const FIELD_TYPE_INT = 0x0009;
    const FIELD_TYPE_TIMESTAMP = 0x000B;
    const FIELD_TYPE_UUID = 0x000C;
    const FIELD_TYPE_VARCHAR = 0x000D;
    const FIELD_TYPE_VARINT = 0x000E;
    const FIELD_TYPE_TIMEUUID = 0x000F;
    const FIELD_TYPE_INET = 0x0010;
    const FIELD_TYPE_DATE = 0x0011;
    const FIELD_TYPE_TIME = 0x0012;
    const FIELD_TYPE_SMALLINT = 0x0013;
    const FIELD_TYPE_TINYINT = 0x0014;
    const FIELD_TYPE_LIST = 0x0020;
    const FIELD_TYPE_MAP = 0x0021;
    const FIELD_TYPE_SET = 0x0022;
    const FIELD_TYPE_UDT = 0x0030;
    const FIELD_TYPE_TUPLE = 0x0031;
}