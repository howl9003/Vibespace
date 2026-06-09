package org.archcave.beans;
import org.archcave.classes.*;

/**
 * Class JavaDoc Description
 *
 * @author Scrubs
 * @version version Nov 2, 2004 3:05:14 PM
 */
public class ACCore {

    private ACPlayersTable PlayersTable;
    private ACUsersTable UsersTable;
    private boolean running;
    private String status;
    public ACCore()
    {
        status = "Down";
        running = false;
        PlayersTable = new ACPlayersTable();
        UsersTable = new ACUsersTable();
        ACUsers.mTable = UsersTable;
        LoadTables();
    }

    private boolean LoadTables()
    {
        if (PlayersTable != null || UsersTable != null)
            return false;
        // Load the Tables
        return true;
    }

    public boolean start()
    {
        running = true;
        status = "Running!";
        // start turn loop (+ timers)?
        return true;
    }

    public boolean stop()
    {
        running = false;
        status = "Stopped";
        // stop turn loop (+ timers)?
        return false;
    }

    public void setStatus(String message)
    {
        status = message;
    }

    public String getStatus()
    {
        return status;
    }
    public boolean isRunning()
    {
        return running;
    }

    public ACPlayersTable getPlayersTable()
    {
        return  PlayersTable;
    }

    public void setPlayersTable(ACPlayersTable aPlayersTable)
    {
        PlayersTable = aPlayersTable;
    }

    public void setUsersTable(ACUsersTable aUsersTable)
    {
        UsersTable = aUsersTable;
    }

    public ACUsersTable getUsersTable()
    {
        return UsersTable;
    }
}