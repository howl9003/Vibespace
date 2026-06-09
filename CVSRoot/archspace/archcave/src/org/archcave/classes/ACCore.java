/*
 * ACCore.java
 *
 * Created on November 3, 2004, 10:21 PM
 */

package org.archcave.classes;

import org.archcave.libs.JStoreStation;
import org.archcave.libs.JCronTabs;

/**
 * note: this is far from finished, we need an xml config loading routine
 * @author  zbyte64
 */
public class ACCore {
    protected static ACBattlesTable mBattlesTable = new ACBattlesTable();
    protected static ACCommandersTable mCommandersTable = new ACCommandersTable();
    protected static ACDesignsTable mDesignsTable = new ACDesignsTable();
    protected static ACEventsTable mEventsTable = new ACEventsTable();
    protected static ACInhabitantsTable mInhabitantsTable = new ACInhabitantsTable();
    protected static ACMessagesTable mMessagesTable =  new ACMessagesTable();
    protected static ACPlayerActionTable mPlayerActionTable =  new ACPlayerActionTable();
    protected static ACPlayersTable mPlayersTable = new ACPlayersTable();
    protected static ACRelationsTable mRelationsTable = new ACRelationsTable();
    protected static ACSquadsTable mSquadsTable =  new ACSquadsTable();
    protected static ACTerritoriesTable mTerritoriesTable = new ACTerritoriesTable();
    protected static ACUnitsTable mUnitsTable = new ACUnitsTable();
    protected static ACUsersTable mUsersTable = new ACUsersTable();
    protected static JStoreStation mStoreStation;
    protected static JCronTabs mCronTabs = new JCronTabs();
    
    public static void init(String aConfigPath) {
        ACConfig.loadConfig(aConfigPath);
        start();
    }

    public static void start() {
        mStoreStation.openConnection();
        
        //load sql data into tables
        mBattlesTable.load(mStoreStation.getStatement());
        mCommandersTable.load(mStoreStation.getStatement());
        mDesignsTable.load(mStoreStation.getStatement());
        mEventsTable.load(mStoreStation.getStatement());
        mInhabitantsTable.load(mStoreStation.getStatement());
        mMessagesTable.load(mStoreStation.getStatement());
        mPlayerActionTable.load(mStoreStation.getStatement());
        mPlayersTable.load(mStoreStation.getStatement());
        mRelationsTable.load(mStoreStation.getStatement());
        mSquadsTable.load(mStoreStation.getStatement());
        mTerritoriesTable.load(mStoreStation.getStatement());
        mUnitsTable.load(mStoreStation.getStatement());
        mUsersTable.load(mStoreStation.getStatement());
        
        //link the objects into the table to each other
    }
    
    public static void stop() {
        mCronTabs.stopTimer();
        mStoreStation.closeConnection();
        mBattlesTable.empty();
        mCommandersTable.empty();
        mDesignsTable.empty();
        mEventsTable.empty();
        mInhabitantsTable.empty();
        mMessagesTable.empty();
        mPlayerActionTable.empty();
        mPlayersTable.empty();
        mRelationsTable.empty();
        mSquadsTable.empty();
        mTerritoriesTable.empty();
        mUnitsTable.empty();
        mUsersTable.empty();
    }
    
    public static void pause() {
        mCronTabs.stopTimer();
    }
    
    public static void resume() {
        mCronTabs.startTimer();
    }
}
