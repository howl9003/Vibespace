package org.archcave.libs;


/**
 *
 * @author  zbyte64
 */
public interface JStore {
    public void executeSQLqueries(java.sql.Statement SQLStatement);
    public void flushSQLsync();
} 
