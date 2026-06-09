/*
 * JStoreStation.java
 *
 * Created on November 1, 2004, 6:38 PM
 */

package org.archcave.libs;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.sql.Statement;
/**
 *
 * @author  zbyte64
 */
public class JStoreStation {
    private java.lang.String ConnectionUrl;
    private Connection conn;
    private Statement sqlStatement;
    private java.util.Vector JStoreVector;
    
    /** Creates a new instance of JStoreStation */
    public JStoreStation(java.lang.String aConnectionUrl) {
        ConnectionUrl = aConnectionUrl;
    }
    
    public void openConnection() {
        try {
            conn = DriverManager.getConnection(ConnectionUrl);
            sqlStatement = conn.createStatement();
        } catch (SQLException ex) {
            // handle any errors 
            JLog.log("SQLException: " + ex.getMessage()); 
            JLog.log("SQLState: " + ex.getSQLState()); 
            JLog.log("VendorError: " + ex.getErrorCode());
        }
    }
    
    public void closeConnection() {
        flushSQLSync();
        try {
            sqlStatement.close();
            conn.close();
        } catch (SQLException ex) {
            // handle any errors 
            JLog.log("SQLException: " + ex.getMessage()); 
            JLog.log("SQLState: " + ex.getSQLState()); 
            JLog.log("VendorError: " + ex.getErrorCode());
        }
    }
    
    public void processSQLSync() {
        JStore aJstore;
        for (int index = 0; index < JStoreVector.size(); index++) {
            aJstore = (JStore)JStoreVector.get(index);
            aJstore.executeSQLqueries(sqlStatement);
        }
    }
    
    public void flushSQLSync() {
        JStore aJstore;
        for (int index = 0; index < JStoreVector.size(); index++) {
            aJstore = (JStore)JStoreVector.get(index);
            aJstore.flushSQLsync();
        }
    }
    
    public void addJStore(JStore aJStore) {
        JStoreVector.add(aJStore);
    }
    
    public void removeJStore(JStore aJStore) {
        JStoreVector.remove(aJStore);
    }
    
    public Statement getStatement() {
        return sqlStatement;
    }
}
