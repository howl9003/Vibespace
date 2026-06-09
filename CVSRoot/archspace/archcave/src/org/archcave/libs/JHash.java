/*
 * JHash.java
 *
 * Created on October 30, 2004, 10:25 PM
 */

package org.archcave.libs;

/**
 *
 * @author  zbyte64
 */
public class JHash {
    private char[] mHash;
    
    /** Creates a new instance of Jhash */
    public JHash(java.lang.String hash) {
        mHash = hash.toCharArray();
    }
    
    public java.lang.String getSQLValue() {
        return org.archcave.libs.JSane.makeSaneSQL(new java.lang.String(mHash));
    }
    
    public boolean hasSet(int aBit) {
        int bytedigit = aBit/8;
        char offset = (char)(aBit - bytedigit*8);
        char setofbits = 0x80;
        setofbits >>= offset;
        if ( 0 < (mHash[bytedigit] & setofbits)) return true;
        return false;
    }
    
    public void setBit(int aBit) {
        int bytedigit = aBit/8;
        char offset = (char)(aBit - bytedigit*8);
        char setofbits = 0x80;
        setofbits >>= offset;
        mHash[bytedigit] |= setofbits;
    }
    
    public char[] getHash() {
        return mHash;
    }
    
    public void setHash(char[] aHash) {
        mHash = aHash;
    }
}
