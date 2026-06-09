package org.archcave.beans;
import org.archcave.classes.ACPlayers;
import org.archcave.classes.ACUsers;
/**
 * Archcave Session beans, holds session and player information
 *
 * @author Brian Kearney
 * @version version Oct 14, 2004 7:03:05 PM
 */
public class SessionBean
{
    private ACPlayers thisPlayer;
    private ACUsers thisUser;
    private boolean active;
    public SessionBean()
    {
        active = false;
    }

    public boolean isActive()
    {
        return active;
    }

    public void activate()
    {
        active = true;
    }

    public void deactivate()
    {
        active = false;
    }

    public ACPlayers getPlayer()
    {
        return thisPlayer;
    }

    public void setPlayer(ACPlayers aPlayer)
    {
        thisPlayer = aPlayer;
    }

    public ACUsers getUser()
    {
        return thisUser;
    }

    public void setUser(ACUsers aUser)
    {
        thisUser = aUser;
    }
}
