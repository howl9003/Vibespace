/*
 * ACConfig.java
 *
 * Created on November 6, 2004, 12:43 AM
 */

package org.archcave.classes;

import org.archcave.libs.JLog;
import org.archcave.libs.JTime;
import org.archcave.libs.JStoreStation;

import java.io.File;
import org.w3c.dom.Document;
import org.w3c.dom.DOMException;
import org.w3c.dom.Element;

import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.DocumentBuilder;
import org.xml.sax.SAXException;
import org.xml.sax.SAXParseException; 
/**
 *
 * @author  zbyte64
 */
public class ACConfig {
    private static String config_path;
    private static String domestic_enhancement_path;
    private static String territory_enhancement_path;
    private static String territory_resource_path;
    private static String event_path;
    private static String commander_ability_path;
    private static String design_chasis_path;
    private static String design_armor_path;
    private static String design_weapon_path;
    private static String design_special_path;
    private static String choices_path;
    private static String policy_path;
    private static String abilities_path;
    private static String tech_path;
    private static String effect_path;
    private static String prerequisite_path;
    
    public static void loadConfig(String aConfigPath) {
        config_path = aConfigPath;
        try {
            //initialize
            DocumentBuilderFactory docBuilderFactory = DocumentBuilderFactory.newInstance();
            DocumentBuilder docBuilder = docBuilderFactory.newDocumentBuilder();
            Document doc = docBuilder.parse(new File(config_path));
            doc.getDocumentElement().normalize();
            
            //grab the config element
            Element XMLConfigElement = (Element)doc.getElementsByTagName("config").item(0);
            
            //Set up logging facility
            JLog.setPath(XMLConfigElement.getElementsByTagName("log_path").item(0).getFirstChild().getNodeValue().trim());
            JLog.openLog();
            
            //Set up mStoreSation
            ACCore.mStoreStation = new JStoreStation(XMLConfigElement.getElementsByTagName("connection_url").item(0).getFirstChild().getNodeValue().trim());
            
            //Set up turn length
            JTime.setTurnLength(Integer.parseInt(XMLConfigElement.getElementsByTagName("turn_length").item(0).getFirstChild().getNodeValue().trim()));
            
            //Set up domestic_enhancement_path
            domestic_enhancement_path = XMLConfigElement.getElementsByTagName("domestic_enhancement_path").item(0).getFirstChild().getNodeValue().trim();

            //Set up territory_enhancement_path
            territory_enhancement_path = XMLConfigElement.getElementsByTagName("territory_enhancement_path").item(0).getFirstChild().getNodeValue().trim();

            //Set up territory_resource_path
            territory_resource_path = XMLConfigElement.getElementsByTagName("territory_resource_path").item(0).getFirstChild().getNodeValue().trim();

            //Set up event_path
            event_path = XMLConfigElement.getElementsByTagName("event_path").item(0).getFirstChild().getNodeValue().trim();;
            
            //Set up commander_ability_path
            commander_ability_path = XMLConfigElement.getElementsByTagName("commander_ability_path").item(0).getFirstChild().getNodeValue().trim();
            
            //Set up design_chasis_path
            design_chasis_path = XMLConfigElement.getElementsByTagName("design_chasis_path").item(0).getFirstChild().getNodeValue().trim();
            
            //Set up design_armor_path
            design_armor_path = XMLConfigElement.getElementsByTagName("design_armor_path").item(0).getFirstChild().getNodeValue().trim();
            
            //Set up design_weapon_path
            design_weapon_path = XMLConfigElement.getElementsByTagName("design_weapon_path").item(0).getFirstChild().getNodeValue().trim();
            
            //Set up design_special_path
            design_special_path = XMLConfigElement.getElementsByTagName("design_special_path").item(0).getFirstChild().getNodeValue().trim();
            
            //Set up choices_path
            choices_path = XMLConfigElement.getElementsByTagName("choices_path").item(0).getFirstChild().getNodeValue().trim();
            
            //Set up policy_path
            policy_path = XMLConfigElement.getElementsByTagName("policy_path").item(0).getFirstChild().getNodeValue().trim();
            
            //Set up abilities_path
            abilities_path = XMLConfigElement.getElementsByTagName("abilities_path").item(0).getFirstChild().getNodeValue().trim();
            
            //Set up tech_path
            tech_path =XMLConfigElement.getElementsByTagName("tech_path").item(0).getFirstChild().getNodeValue().trim();
            
            //Set up effect_path
            effect_path = XMLConfigElement.getElementsByTagName("effect_path").item(0).getFirstChild().getNodeValue().trim();
            
            //Set up prequisite_path
            prerequisite_path = XMLConfigElement.getElementsByTagName("prerequisite_path").item(0).getFirstChild().getNodeValue().trim();
            
        } catch (SAXParseException err) {
            JLog.safeLog("** Parsing error" + ", line " + err.getLineNumber () + ", uri " + err.getSystemId ());
            JLog.safeLog(" " + err.getMessage ());
        } catch (SAXException e) {
            Exception x = e.getException ();
            ((x == null) ? e : x).printStackTrace ();
        } catch (DOMException t) {
            JLog.safeLog("While loading config: "+t.getMessage());
        } catch (Throwable t) {
            t.printStackTrace ();
        }
    }
}
