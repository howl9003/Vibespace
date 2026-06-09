/*
 * ACControlModel.java
 *
 * Created on November 5, 2004, 5:43 PM
 */

package org.archcave.classes;
import org.archcave.libs.JLog;

import org.w3c.dom.DOMException;
import org.w3c.dom.Element;

/**
 *
 * @author  zbyte64
 */
public class ACControlModel {
    private byte production;
    private byte trade;
    private byte research;
    private byte military;
    private byte resource;
    private byte nature;
    private byte mysticism;
    private byte morale;
    private byte agriculture;
    private byte development;
    private byte spy;
    private byte tactics;
    
    /** Creates a new instance of ACControlModel */
    public ACControlModel() {
    }
    
    public ACControlModel(Element XMLCM) {
        try {
            production = (byte)Integer.parseInt(XMLCM.getElementsByTagName("production").item(0).getFirstChild().getNodeValue().trim());
            trade = (byte)Integer.parseInt(XMLCM.getElementsByTagName("trade").item(0).getFirstChild().getNodeValue().trim());
            research = (byte)Integer.parseInt(XMLCM.getElementsByTagName("research").item(0).getFirstChild().getNodeValue().trim());
            military = (byte)Integer.parseInt(XMLCM.getElementsByTagName("military").item(0).getFirstChild().getNodeValue().trim());
            resource = (byte)Integer.parseInt(XMLCM.getElementsByTagName("resource").item(0).getFirstChild().getNodeValue().trim());
            nature = (byte)Integer.parseInt(XMLCM.getElementsByTagName("nature").item(0).getFirstChild().getNodeValue().trim());
            mysticism = (byte)Integer.parseInt(XMLCM.getElementsByTagName("mysticism").item(0).getFirstChild().getNodeValue().trim());
            morale = (byte)Integer.parseInt(XMLCM.getElementsByTagName("morale").item(0).getFirstChild().getNodeValue().trim());
            agriculture = (byte)Integer.parseInt(XMLCM.getElementsByTagName("agriculture").item(0).getFirstChild().getNodeValue().trim());
            development = (byte)Integer.parseInt(XMLCM.getElementsByTagName("development").item(0).getFirstChild().getNodeValue().trim());
            spy = (byte)Integer.parseInt(XMLCM.getElementsByTagName("spy").item(0).getFirstChild().getNodeValue().trim());
            tactics = (byte)Integer.parseInt(XMLCM.getElementsByTagName("tactics").item(0).getFirstChild().getNodeValue().trim());
        } catch (DOMException t) {
            JLog.log("While loading CM: "+t.getMessage());
        }
    }
    
    public ACControlModel getNetCM(ACControlModel aCM) {
        ACControlModel rCM = new ACControlModel();
        rCM.setProduction((byte)(aCM.getProduction()+production));
        rCM.setTrade((byte)(aCM.getTrade()+trade));
        rCM.setResearch((byte)(aCM.getResearch()+research));
        rCM.setMilitary((byte)(aCM.getMilitary()+military));
        rCM.setResource((byte)(aCM.getResource()+resource));
        rCM.setNature((byte)(aCM.getNature()+nature));
        rCM.setMysticism((byte)(aCM.getMysticism()+mysticism));
        rCM.setMorale((byte)(aCM.getMorale()+morale));
        rCM.setAgriculture((byte)(aCM.getAgriculture()+agriculture));
        rCM.setDevelopment((byte)(aCM.getDevelopment()+development));
        rCM.setSpy((byte)(aCM.getSpy()+spy));
        rCM.setTactics((byte)(aCM.getTactics()+tactics));
        return rCM;
    }
    
    public void addCM(ACControlModel aCM) {
        production += aCM.getProduction();
        trade += aCM.getTrade();
        research += aCM.getResearch();
        military += aCM.getMilitary();
        resource += aCM.getResource();
        nature += aCM.getNature();
        mysticism += aCM.getMysticism();
        morale += aCM.getMorale();
        agriculture += aCM.getAgriculture();
        development += aCM.getDevelopment();
        spy += aCM.getSpy();
        tactics += aCM.getTactics();
    }
    
    /** Getter for property agriculture.
     * @return Value of property agriculture.
     *
     */
    public byte getAgriculture() {
        return agriculture;
    }
    
    /** Setter for property agriculture.
     * @param agriculture New value of property agriculture.
     *
     */
    public void setAgriculture(byte agriculture) {
        this.agriculture = agriculture;
    }
    
    /** Getter for property development.
     * @return Value of property development.
     *
     */
    public byte getDevelopment() {
        return development;
    }
    
    /** Setter for property development.
     * @param development New value of property development.
     *
     */
    public void setDevelopment(byte development) {
        this.development = development;
    }
    
    /** Getter for property military.
     * @return Value of property military.
     *
     */
    public byte getMilitary() {
        return military;
    }
    
    /** Setter for property military.
     * @param military New value of property military.
     *
     */
    public void setMilitary(byte military) {
        this.military = military;
    }
    
    /** Getter for property morale.
     * @return Value of property morale.
     *
     */
    public byte getMorale() {
        return morale;
    }
    
    /** Setter for property morale.
     * @param morale New value of property morale.
     *
     */
    public void setMorale(byte morale) {
        this.morale = morale;
    }
    
    /** Getter for property mysticism.
     * @return Value of property mysticism.
     *
     */
    public byte getMysticism() {
        return mysticism;
    }
    
    /** Setter for property mysticism.
     * @param mysticism New value of property mysticism.
     *
     */
    public void setMysticism(byte mysticism) {
        this.mysticism = mysticism;
    }
    
    /** Getter for property nature.
     * @return Value of property nature.
     *
     */
    public byte getNature() {
        return nature;
    }
    
    /** Setter for property nature.
     * @param nature New value of property nature.
     *
     */
    public void setNature(byte nature) {
        this.nature = nature;
    }
    
    /** Getter for property production.
     * @return Value of property production.
     *
     */
    public byte getProduction() {
        return production;
    }
    
    /** Setter for property production.
     * @param production New value of property production.
     *
     */
    public void setProduction(byte production) {
        this.production = production;
    }
    
    /** Getter for property research.
     * @return Value of property research.
     *
     */
    public byte getResearch() {
        return research;
    }
    
    /** Setter for property research.
     * @param research New value of property research.
     *
     */
    public void setResearch(byte research) {
        this.research = research;
    }
    
    /** Getter for property resource.
     * @return Value of property resource.
     *
     */
    public byte getResource() {
        return resource;
    }
    
    /** Setter for property resource.
     * @param resource New value of property resource.
     *
     */
    public void setResource(byte resource) {
        this.resource = resource;
    }
    
    /** Getter for property spy.
     * @return Value of property spy.
     *
     */
    public byte getSpy() {
        return spy;
    }
    
    /** Setter for property spy.
     * @param spy New value of property spy.
     *
     */
    public void setSpy(byte spy) {
        this.spy = spy;
    }
    
    /** Getter for property tactics.
     * @return Value of property tactics.
     *
     */
    public byte getTactics() {
        return tactics;
    }
    
    /** Setter for property tactics.
     * @param tactics New value of property tactics.
     *
     */
    public void setTactics(byte tactics) {
        this.tactics = tactics;
    }
    
    /** Getter for property trade.
     * @return Value of property trade.
     *
     */
    public byte getTrade() {
        return trade;
    }
    
    /** Setter for property trade.
     * @param trade New value of property trade.
     *
     */
    public void setTrade(byte trade) {
        this.trade = trade;
    }
    
}
