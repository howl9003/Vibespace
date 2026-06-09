/*
 * JConversion.java
 *
 * Created on November 1, 2004, 6:37 PM
 */

package org.archcave.libs;

/**
 *
 * @author  zbyte64
 */
public class JConversion {
    
    /** Creates a new instance of JConversion */
    public JConversion() {
    }
    
    public static boolean getBoolean(int aVar) {
        if ( aVar > 0) return true;
        return false;
    }
    
    public static boolean getBoolean(byte aVar) {
        if ( aVar > 0) return true;
        return false;
    }
    
    public static byte getByte(boolean aVar) {
        if (aVar) return 1;
        return 0;
    }
}
