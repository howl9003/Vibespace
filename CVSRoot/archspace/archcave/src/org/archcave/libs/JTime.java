/*
 * JTime.java
 *
 * Created on November 1, 2004, 6:37 PM
 */

package org.archcave.libs;

import java.util.Calendar;
import java.util.Date;

/**
 *
 * @author  zbyte64
 */
public class JTime {
    private static int mTurnLength;
    public static int mCurrentTurn;
    public static Calendar JCalendar;
    
    public static void setTurnLength(int aSeconds) {
        mTurnLength = aSeconds;
    }
    
    public static int getTurnLength() {
        return mTurnLength;
    }
    
    public static void setCurrentTurn(int aTurn) {
        mCurrentTurn = aTurn;
    }
    
    public static int getCurrentTurn() {
        return mCurrentTurn;
    }
    
    public static String getTime() {
        return JCalendar.getTime().toString();
    }
    
    public static Date getFutureDateFromTurns(int aTurns) {
        Date aDate = JCalendar.getTime();
        aDate.setTime(aDate.getTime()+aTurns*mTurnLength);
        return aDate;
    }
    
    public static Date getDateFromTurn(int aTurn) {
        Date aDate = JCalendar.getTime();
        aDate.setTime(aDate.getTime()+(aTurn-mCurrentTurn)*mTurnLength);
        return aDate;
    }
}
