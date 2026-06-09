/*
 * ACPrerequisiteElement.java
 *
 * Created on November 6, 2004, 6:46 PM
 */

package org.archcave.classes;

//import java.util.Enumeration;

import org.archcave.libs.JLog;

import org.w3c.dom.DOMException;
import org.w3c.dom.Element;
import org.w3c.dom.Node;

/**
 *
 * @author  zbyte64
 */
public class ACPrerequisiteElement {
    private byte mOperator;
    private int mType;
    private int mValue;
    
    public static final byte E_NOT = 0;
    public static final byte E_EQUAL = 1;
    public static final byte E_LESS_THEN = 2;
    public static final byte E_LESS_OR_EQUAL = 3;
    public static final byte E_GREATER_OR_EQUAL = 4;
    public static final byte E_GREATER_THEN = 5;
    
    public static final String[] eOperators = {
        "not",
        "equal",
        "less_then",
        "less_or_equal",
        "greater_or_equal",
        "greater_then",
    };
    
    public static final String[] eBools = {
        "false",
        "true",
    };
    
    /** Creates a new instance of ACPrerequisiteElement */
    public ACPrerequisiteElement(Node XMLPrerequisite, int aType) {
        try {
            mType = aType;
            String aOperator = XMLPrerequisite.getAttributes().getNamedItem("operator").getNodeValue().trim();
            for (byte index = 0; index < eBools.length; index++) {
                if (0 == aOperator.compareToIgnoreCase(eBools[(int)index])) {
                    mOperator = index;
                    return;
                }
            }
            mValue = Integer.parseInt(XMLPrerequisite.getNodeValue().trim());
            for (byte index = 0; index < eOperators.length; index++) {
                if (0 == aOperator.compareToIgnoreCase(eOperators[(int)index])) {
                    mOperator = index;
                    return;
                }
            }
            String aName = XMLPrerequisite.getNodeName().trim();
            JLog.log("Error parsing prequisite element - invalid operator: stype="+aName+" value="+mValue+" operator="+aOperator);
        } catch (DOMException t) {
            JLog.log("While loading prequisite element: "+t.getMessage());
        }
    }
    
    public boolean evaluate(ACPlayers aPlayer) {
        switch(mType) {
            case 0:
                break;
        }
        return false;
    }
    
    public String getDescription() {
        return "";
    }
}
