/*
 * JSane.java
 *
 * Created on October 31, 2004, 10:42 PM
 */

package org.archcave.libs;



/**
 *
 * @author  zbyte64
 */
public class JSane {
    
    /** Creates a new instance of Jsane */
    public JSane() {
    }
    
    public static java.lang.String makeSaneHTML(java.lang.String arg) {
        java.lang.String result = arg.replaceAll("<","&#060;");
        result = result.replaceAll(">","&#062;");
        return result;
    }
    
    public static java.lang.String makeSaneSQL(java.lang.String arg) {
        java.lang.String result = arg.replaceAll("\\","\\\\");
        result = result.replaceAll("'","\\'");
        return result;
    }
}
