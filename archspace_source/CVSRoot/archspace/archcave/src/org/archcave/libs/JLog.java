/*
 * JLog.java
 *
 * Created on November 1, 2004, 6:48 PM
 */

package org.archcave.libs;

import java.io.FileOutputStream;
import java.io.PrintWriter;

/**
 *
 * @author  zbyte64
 */
public class JLog {
    private static java.lang.String logFileName;
    private static PrintWriter logFile;

    public static void setPath(java.lang.String aPath) {
        logFileName = aPath;
    }
    
    public static void openLog() {
        try {
            logFile = new PrintWriter(new FileOutputStream(logFileName));
        } catch (java.io.FileNotFoundException ex) {
            System.out.println("Log file not found: "+logFileName);
            System.out.println(ex.toString());
        }
    }
    
    public static void openLog(java.lang.String aPath) {
        setPath(aPath);
        openLog();
    }
    
    public static void closeLog() {
        logFile.close();
    }
    
    public static void flushLog() {
        
    }
    
    public static void log(java.lang.String aText) {
        logFile.println(JTime.getTime()+" | "+aText);
    }
    
    public static void safeLog(java.lang.String aText) {
        if (logFile == null) {
            System.out.println(JTime.getTime()+" | "+aText);
        } else {
            logFile.println(JTime.getTime()+" | "+aText);
        }
    }
    
}
