/*
 * JCronTabs.java
 *
 * Created on November 1, 2004, 6:38 PM
 */

package org.archcave.libs;

import java.util.Timer;
import java.util.TimerTask;
import java.util.Vector;

/**
 *
 * @author  zbyte64
 */
public class JCronTabs {
    java.util.Vector mCronVector = new Vector();
    
    /** Creates a new instance of JCronTabs */
    public JCronTabs() {
    }
    
    public void addCronTab(TimerTask aTask, int aIntervals, int aDelay) {
        mCronVector.add(new JCron(aTask,aIntervals,aDelay));
    }
    
    public void addCronTab(TimerTask aTask, int aIntervals) {
        mCronVector.add(new JCron(aTask,aIntervals,0));
    }
    
    public void removeCronTab(int index) {
        stopCron(index);
        mCronVector.remove(index);
    }
    
    public void removeCronTab(TimerTask aTask) {
        removeCronTab(findCron(aTask));
    }
    
    public void stopTimer() {
        JCron aCron;
        for (int index = 0; index < mCronVector.size(); index++) {
            aCron = (JCron)mCronVector.get(index);
            aCron.stop();
        }
    }
    
    public void startTimer() {
        JCron aCron;
        for (int index = 0; index < mCronVector.size(); index++) {
            aCron = (JCron)mCronVector.get(index);
            aCron.run();
        }
    }
    
    public void stopCron(int index) {
        JCron aCron = (JCron)mCronVector.get(index);
        aCron.stop();
    }
    
    public void stopCron(TimerTask aTask) {
        stopCron(findCron(aTask));
    }
    
    public void startCron(int index) {
        JCron aCron = (JCron)mCronVector.get(index);
        aCron.run();
    }
    
    public void startCron(TimerTask aTask) {
        startCron(findCron(aTask));
    }
    
    public int findCron(TimerTask aTask) {
        JCron aCron;
        for (int index = 0; index < mCronVector.size(); index++) {
            aCron = (JCron)mCronVector.get(index);
            if (aTask.equals(aCron.mTask)) return index;
        }
        return -1;
    }
    
    public class JCron {
        private int mIntervalSeconds;
        private int mDelaySeconds;
        public TimerTask mTask;
        private Timer mTimer =  new Timer();
        /** Creates a new instance of JCron */
        public JCron(TimerTask aTask, int aDelaySeconds, int aIntervalSeconds) {
            mTask= aTask;
            mDelaySeconds = aDelaySeconds;
            mIntervalSeconds = aIntervalSeconds;
        }
        
        public void run() {
            mTimer.schedule(mTask,mDelaySeconds*1000,mIntervalSeconds*1000);
        }
        
        public void stop() {
            mTimer.cancel();
        }
    }
  
}
