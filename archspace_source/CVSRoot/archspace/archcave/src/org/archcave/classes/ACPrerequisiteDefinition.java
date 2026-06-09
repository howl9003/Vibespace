/*
 * ACPrerequisiteDefinition.java
 *
 * Created on November 6, 2004, 6:47 PM
 */

package org.archcave.classes;

import java.util.Vector;

import org.archcave.libs.JLog;

import org.w3c.dom.DOMException;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
/**
 *
 * @author  zbyte64
 */
public class ACPrerequisiteDefinition {
    private Vector mPrerequisiteElementVector = new Vector();
    private String mScriptPath;
    
    public static final String[] eType = {
        "example"
    };
    
    /** Creates a new instance of ACPrerequisiteDefinition */
    public ACPrerequisiteDefinition(Element XMLPrerequisite) {
        for (int index = 0; index < eType.length; index++) {
            parseElement(XMLPrerequisite, eType[index],  index);
        }
    }
    
    public boolean canHave(ACPlayers aPlayer) {
        for (int index = 0; index < mPrerequisiteElementVector.size(); index++) {
            if (! ((ACPrerequisiteElement)mPrerequisiteElementVector.get(index)).evaluate(aPlayer)) return false;
        }
        return true;
    }
    
    public String getHTMLPrerequisiteDescription() {
        String aResult = new String();
        for (int index = 0; index < mPrerequisiteElementVector.size(); index++) {
            aResult += ((ACPrerequisiteElement)mPrerequisiteElementVector.get(index)).getDescription();
        }
        return aResult;
    }
    
    private void parseElement(Element XMLPrequisite, String aName, int aType) {
        try {
            NodeList aResults = XMLPrequisite.getElementsByTagName(aName);
            ACPrerequisiteElement aPrerequisiteElement;
            for (int index = 0; index < aResults.getLength(); index++) {
                aPrerequisiteElement = new ACPrerequisiteElement(aResults.item(index),aType);
                mPrerequisiteElementVector.add(aPrerequisiteElement);
            }
        } catch (DOMException t) {
            JLog.log("While loading prequisite: "+t.getMessage());
        }
    } 
}
