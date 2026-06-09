/***********************************
 building.js
 by telecard
***********************************/
  check = new Image();
  check.src = "http://space.magewar.com/image/as_game/domestic/planet_check.gif";
  
  box = new Image();
  box.src = "http://space.magewar.com/image/as_game/domestic/planet_box.gif";
  
  lock = new Image();
  lock.src = "http://space.magewar.com/image/as_game/domestic/lock.gif";
  
  unlock = new Image();
  unlock.src = "http://space.magewar.com/image/as_game/domestic/unlock.gif";

  var factory_value = 0;
  var laboratory_value = 0;
  var military_value = 0;

  var factory_locked = false;
  var laboratory_locked = false;
  var military_locked = false;

  function factory_up()
  {
    if(factory_value<100 && factory_locked==false)
    {
      if(laboratory_locked==false && laboratory_value>0)
      {
        if(military_locked==false && military_value>0)
        {
          if(laboratory_value>military_value)
          {
            factory_value = factory_value + 10;
            setFactory(factory_value);
            laboratory_value = laboratory_value - 10;
            setLaboratory(laboratory_value);
          }
          else
          {
            factory_value = factory_value + 10;
            setFactory(factory_value);
            military_value = military_value - 10;
            setMilitary(military_value);
          }
        }
        else
        {
          factory_value = factory_value + 10;
          setFactory(factory_value);
          laboratory_value = laboratory_value - 10;
          setLaboratory(laboratory_value);
        }
      }
      else if(military_locked==false && military_value>0)
      {
        factory_value = factory_value + 10;
        setFactory(factory_value);
        military_value = military_value - 10;
        setMilitary(military_value);
      }
    }
  }


  function factory_down()
  {
    if(factory_value>0 && factory_locked==false)
    {
      if(laboratory_locked==false && laboratory_value<100)
      {
        if(military_locked==false && military_value<100)
        {
          if(laboratory_value<military_value)
          {
            factory_value = factory_value - 10;
            setFactory(factory_value);
            laboratory_value = laboratory_value + 10;
            setLaboratory(laboratory_value);
          }
          else
          {
            factory_value = factory_value - 10;
            setFactory(factory_value);
            military_value = military_value + 10;
            setMilitary(military_value);
          }
        }
        else
        {
          factory_value = factory_value - 10;
          setFactory(factory_value);
          laboratory_value = laboratory_value + 10;
          setLaboratory(laboratory_value);
        }
      }
      else if(military_locked==false && military_value<100)
      {
        factory_value = factory_value - 10;
        setFactory(factory_value);
        military_value = military_value + 10;
        setMilitary(military_value);
      }
    }
  }


  function laboratory_up()
  {
    if(laboratory_value<100 && laboratory_locked==false)
    {
      if(military_locked==false && military_value>0)
      {
        if(factory_locked==false && factory_value>0)
        {
          if(military_value>factory_value)
          {
            laboratory_value = laboratory_value + 10;
            setLaboratory(laboratory_value);
            military_value = military_value - 10;
            setMilitary(military_value);
          }
          else
          {
            laboratory_value = laboratory_value + 10;
            setLaboratory(laboratory_value);
            factory_value = factory_value - 10;
            setFactory(factory_value);
          }
        }
        else
        {
          laboratory_value = laboratory_value + 10;
          setLaboratory(laboratory_value);
          military_value = military_value - 10;
          setMilitary(military_value);
        }
      }
      else if(factory_locked==false && factory_value>0)
      {
        laboratory_value = laboratory_value + 10;
        setLaboratory(laboratory_value);
        factory_value = factory_value - 10;
        setFactory(factory_value);
      }
    }
  }


  function laboratory_down()
  {
    if(laboratory_value>0 && laboratory_locked==false)
    {
      if(military_locked==false && military_value<100)
      {
        if(factory_locked==false && factory_value<100)
        {
          if(military_value<factory_value)
          {
            laboratory_value = laboratory_value - 10;
            setLaboratory(laboratory_value);
            military_value = military_value + 10;
            setMilitary(military_value);
          }
          else
          {
            laboratory_value = laboratory_value - 10;
            setLaboratory(laboratory_value);
            factory_value = factory_value + 10;
            setFactory(factory_value);
          }
        }
        else
        {
          laboratory_value = laboratory_value - 10;
          setLaboratory(laboratory_value);
          military_value = military_value + 10;
          setMilitary(military_value);
        }
      }
      else if(factory_locked==false && factory_value<100)
      {
        laboratory_value = laboratory_value - 10;
        setLaboratory(laboratory_value);
        factory_value = factory_value + 10;
        setFactory(factory_value);
      }
    }
  }


  function military_up()
  {
    if(military_value<100 && military_locked==false)
    {
      if(factory_locked==false && factory_value>0)
      {
        if(laboratory_locked==false && laboratory_value>0)
        {
          if(factory_value>laboratory_value)
          {
            military_value = military_value + 10;
            setMilitary(military_value);
            factory_value = factory_value - 10;
            setFactory(factory_value);
          }
          else
          {
            military_value = military_value + 10;
            setMilitary(military_value);
            laboratory_value = laboratory_value - 10;
            setLaboratory(laboratory_value);
          }
        }
        else
        {
          military_value = military_value + 10;
          setMilitary(military_value);
          factory_value = factory_value - 10;
          setFactory(factory_value);
        }
      }
      else if(laboratory_locked==false && laboratory_value>0)
      {
        military_value = military_value + 10;
        setMilitary(military_value);
        laboratory_value = laboratory_value - 10;
        setLaboratory(laboratory_value);
      }
    }
  }


  function military_down()
  {
    if(military_value>0 && military_locked==false)
    {
      if(factory_locked==false && factory_value<100)
      {
        if(laboratory_locked==false && laboratory_value<100)
        {
          if(factory_value<laboratory_value)
          {
            military_value = military_value - 10;
            setMilitary(military_value);
            factory_value = factory_value + 10;
            setFactory(factory_value);
          }
          else
          {
            military_value = military_value - 10;
            setMilitary(military_value);
            laboratory_value = laboratory_value + 10;
            setLaboratory(laboratory_value);
          }
        }
        else
        {
          military_value = military_value - 10;
          setMilitary(military_value);
          factory_value = factory_value + 10;
          setFactory(factory_value);
        }
      }
      else if(laboratory_locked==false && laboratory_value>0)
      {
        military_value = military_value - 10;
        setMilitary(military_value);
        laboratory_value = laboratory_value + 10;
        setLaboratory(laboratory_value);
      }
    }
  }


  function setFactory(index)
  {
    form1.factory.value = index;
    if(index == 0)
    {
      document.images.factory1.src = box.src;
      document.images.factory2.src = box.src;
      document.images.factory3.src = box.src;
      document.images.factory4.src = box.src;
      document.images.factory5.src = box.src;
      document.images.factory6.src = box.src;
      document.images.factory7.src = box.src;
      document.images.factory8.src = box.src;
      document.images.factory9.src = box.src;
      document.images.factory10.src = box.src;
    }
    else if(index == 10)
    {
      document.images.factory1.src = check.src;
      document.images.factory2.src = box.src;
      document.images.factory3.src = box.src;
      document.images.factory4.src = box.src;
      document.images.factory5.src = box.src;
      document.images.factory6.src = box.src;
      document.images.factory7.src = box.src;
      document.images.factory8.src = box.src;
      document.images.factory9.src = box.src;
      document.images.factory10.src = box.src;
    }
    else if(index == 20)
    {
      document.images.factory1.src = check.src;
      document.images.factory2.src = check.src;
      document.images.factory3.src = box.src;
      document.images.factory4.src = box.src;
      document.images.factory5.src = box.src;
      document.images.factory6.src = box.src;
      document.images.factory7.src = box.src;
      document.images.factory8.src = box.src;
      document.images.factory9.src = box.src;
      document.images.factory10.src = box.src;
    }
    else if(index == 30)
    {
      document.images.factory1.src = check.src;
      document.images.factory2.src = check.src;
      document.images.factory3.src = check.src;
      document.images.factory4.src = box.src;
      document.images.factory5.src = box.src;
      document.images.factory6.src = box.src;
      document.images.factory7.src = box.src;
      document.images.factory8.src = box.src;
      document.images.factory9.src = box.src;
      document.images.factory10.src = box.src;
    }
    else if(index == 40)
    {
      document.images.factory1.src = check.src;
      document.images.factory2.src = check.src;
      document.images.factory3.src = check.src;
      document.images.factory4.src = check.src;
      document.images.factory5.src = box.src;
      document.images.factory6.src = box.src;
      document.images.factory7.src = box.src;
      document.images.factory8.src = box.src;
      document.images.factory9.src = box.src;
      document.images.factory10.src = box.src;
     }
     else if(index == 50)
     {
       document.images.factory1.src = check.src;
       document.images.factory2.src = check.src;
       document.images.factory3.src = check.src;
       document.images.factory4.src = check.src;
       document.images.factory5.src = check.src;
       document.images.factory6.src = box.src;
       document.images.factory7.src = box.src;
       document.images.factory8.src = box.src;
       document.images.factory9.src = box.src;
       document.images.factory10.src = box.src;
      }
      else if(index == 60)
      {
        document.images.factory1.src = check.src;
      	document.images.factory2.src = check.src;
      	document.images.factory3.src = check.src;
        document.images.factory4.src = check.src;
      	document.images.factory5.src = check.src;
      	document.images.factory6.src = check.src;
      	document.images.factory7.src = box.src;
      	document.images.factory8.src = box.src;
      	document.images.factory9.src = box.src;
      	document.images.factory10.src = box.src;
      }

      else if(index == 70)
      {
        document.images.factory1.src = check.src;
      	document.images.factory2.src = check.src;
      	document.images.factory3.src = check.src;
      	document.images.factory4.src = check.src;
      	document.images.factory5.src = check.src;
      	document.images.factory6.src = check.src;
      	document.images.factory7.src = check.src;
      	document.images.factory8.src = box.src;
      	document.images.factory9.src = box.src;
      	document.images.factory10.src = box.src;
      }

      else if(index == 80)
      {
        document.images.factory1.src = check.src;
      	document.images.factory2.src = check.src;
      	document.images.factory3.src = check.src;
      	document.images.factory4.src = check.src;
      	document.images.factory5.src = check.src;
      	document.images.factory6.src = check.src;
      	document.images.factory7.src = check.src;
      	document.images.factory8.src = check.src;
      	document.images.factory9.src = box.src;
      	document.images.factory10.src = box.src;
      }

      else if(index == 90)
      {
        document.images.factory1.src = check.src;
      	document.images.factory2.src = check.src;
      	document.images.factory3.src = check.src;
      	document.images.factory4.src = check.src;
      	document.images.factory5.src = check.src;
      	document.images.factory6.src = check.src;
      	document.images.factory7.src = check.src;
      	document.images.factory8.src = check.src;
     	document.images.factory9.src = check.src;
      	document.images.factory10.src = box.src;
      }
      else if(index == 100)
      {
        document.images.factory1.src = check.src;
      	document.images.factory2.src = check.src;
      	document.images.factory3.src = check.src;
      	document.images.factory4.src = check.src;
      	document.images.factory5.src = check.src;
      	document.images.factory6.src = check.src;
      	document.images.factory7.src = check.src;
      	document.images.factory8.src = check.src;
      	document.images.factory9.src = check.src;
      	document.images.factory10.src = check.src;
      }
    }


  function setLaboratory(index)
  {
    form1.laboratory.value = index;
    if(index == 0)
    {
      document.images.laboratory1.src = box.src;
      document.images.laboratory2.src = box.src;
      document.images.laboratory3.src = box.src;
      document.images.laboratory4.src = box.src;
      document.images.laboratory5.src = box.src;
      document.images.laboratory6.src = box.src;
      document.images.laboratory7.src = box.src;
      document.images.laboratory8.src = box.src;
      document.images.laboratory9.src = box.src;
      document.images.laboratory10.src = box.src;
    }

    else if(index == 10)
    {
      document.images.laboratory1.src = check.src;
      document.images.laboratory2.src = box.src;
      document.images.laboratory3.src = box.src;
      document.images.laboratory4.src = box.src;
      document.images.laboratory5.src = box.src;
      document.images.laboratory6.src = box.src;
      document.images.laboratory7.src = box.src;
      document.images.laboratory8.src = box.src;
      document.images.laboratory9.src = box.src;
      document.images.laboratory10.src = box.src;
    }

    else if(index == 20)
    {
      document.images.laboratory1.src = check.src;
      document.images.laboratory2.src = check.src;
      document.images.laboratory3.src = box.src;
      document.images.laboratory4.src = box.src;
      document.images.laboratory5.src = box.src;
      document.images.laboratory6.src = box.src;
      document.images.laboratory7.src = box.src;
      document.images.laboratory8.src = box.src;
      document.images.laboratory9.src = box.src;
      document.images.laboratory10.src = box.src;
    }
    else if(index == 30)
    {
      document.images.laboratory1.src = check.src;
      document.images.laboratory2.src = check.src;
      document.images.laboratory3.src = check.src;
      document.images.laboratory4.src = box.src;
      document.images.laboratory5.src = box.src;
      document.images.laboratory6.src = box.src;
      document.images.laboratory7.src = box.src;
      document.images.laboratory8.src = box.src;
      document.images.laboratory9.src = box.src;
      document.images.laboratory10.src = box.src;
    }

    else if(index == 40)
    {
      document.images.laboratory1.src = check.src;
      document.images.laboratory2.src = check.src;
      document.images.laboratory3.src = check.src;
      document.images.laboratory4.src = check.src;
      document.images.laboratory5.src = box.src;
      document.images.laboratory6.src = box.src;
      document.images.laboratory7.src = box.src;
      document.images.laboratory8.src = box.src;
      document.images.laboratory9.src = box.src;
      document.images.laboratory10.src = box.src;
     }
     else if(index == 50)
    {
      document.images.laboratory1.src = check.src;
      document.images.laboratory2.src = check.src;
      document.images.laboratory3.src = check.src;
      document.images.laboratory4.src = check.src;
      document.images.laboratory5.src = check.src;
      document.images.laboratory6.src = box.src;
      document.images.laboratory7.src = box.src;
      document.images.laboratory8.src = box.src;
      document.images.laboratory9.src = box.src;
      document.images.laboratory10.src = box.src;
    }

    else if(index == 60)
    {
      document.images.laboratory1.src = check.src;
      document.images.laboratory2.src = check.src;
      document.images.laboratory3.src = check.src;
      document.images.laboratory4.src = check.src;
      document.images.laboratory5.src = check.src;
      document.images.laboratory6.src = check.src;
      document.images.laboratory7.src = box.src;
      document.images.laboratory8.src = box.src;
      document.images.laboratory9.src = box.src;
      document.images.laboratory10.src = box.src;
    }

    else if(index == 70)
    {
      document.images.laboratory1.src = check.src;
      document.images.laboratory2.src = check.src;
      document.images.laboratory3.src = check.src;
      document.images.laboratory4.src = check.src;
      document.images.laboratory5.src = check.src;
      document.images.laboratory6.src = check.src;
      document.images.laboratory7.src = check.src;
      document.images.laboratory8.src = box.src;
      document.images.laboratory9.src = box.src;
      document.images.laboratory10.src = box.src;
    }

    else if(index == 80)
    {
      document.images.laboratory1.src = check.src;
      document.images.laboratory2.src = check.src;
      document.images.laboratory3.src = check.src;
      document.images.laboratory4.src = check.src;
      document.images.laboratory5.src = check.src;
      document.images.laboratory6.src = check.src;
      document.images.laboratory7.src = check.src;
      document.images.laboratory8.src = check.src;
      document.images.laboratory9.src = box.src;
      document.images.laboratory10.src = box.src;
    }
    else if(index == 90)
    {
      document.images.laboratory1.src = check.src;
      document.images.laboratory2.src = check.src;
      document.images.laboratory3.src = check.src;
      document.images.laboratory4.src = check.src;
      document.images.laboratory5.src = check.src;
      document.images.laboratory6.src = check.src;
      document.images.laboratory7.src = check.src;
      document.images.laboratory8.src = check.src;
      document.images.laboratory9.src = check.src;
      document.images.laboratory10.src = box.src;
    }

    else if(index == 100)
    {
      document.images.laboratory1.src = check.src;
      document.images.laboratory2.src = check.src;
      document.images.laboratory3.src = check.src;
      document.images.laboratory4.src = check.src;
      document.images.laboratory5.src = check.src;
      document.images.laboratory6.src = check.src;
      document.images.laboratory7.src = check.src;
      document.images.laboratory8.src = check.src;
      document.images.laboratory9.src = check.src;
      document.images.laboratory10.src = check.src;
    }
  }


  function setMilitary(index)
  {
    form1.military.value = index;
    if(index == 0)
    {
      document.images.military1.src = box.src;
      document.images.military2.src = box.src;
      document.images.military3.src = box.src;
      document.images.military4.src = box.src;
      document.images.military5.src = box.src;
      document.images.military6.src = box.src;
      document.images.military7.src = box.src;
      document.images.military8.src = box.src;
      document.images.military9.src = box.src;
      document.images.military10.src = box.src;
    }

    else if(index == 10)
    {
      document.images.military1.src = check.src;
      document.images.military2.src = box.src;
      document.images.military3.src = box.src;
      document.images.military4.src = box.src;
      document.images.military5.src = box.src;
      document.images.military6.src = box.src;
      document.images.military7.src = box.src;
      document.images.military8.src = box.src;
      document.images.military9.src = box.src;
      document.images.military10.src = box.src;
    }

    else if(index == 20)
    {
      document.images.military1.src = check.src;
      document.images.military2.src = check.src;
      document.images.military3.src = box.src;
      document.images.military4.src = box.src;
      document.images.military5.src = box.src;
      document.images.military6.src = box.src;
      document.images.military7.src = box.src;
      document.images.military8.src = box.src;
      document.images.military9.src = box.src;
      document.images.military10.src = box.src;
    }

    else if(index == 30)
    {
      document.images.military1.src = check.src;
      document.images.military2.src = check.src;
      document.images.military3.src = check.src;
      document.images.military4.src = box.src;
      document.images.military5.src = box.src;
      document.images.military6.src = box.src;
      document.images.military7.src = box.src;
      document.images.military8.src = box.src;
      document.images.military9.src = box.src;
      document.images.military10.src = box.src;
     }

     else if(index == 40)
     {
       document.images.military1.src = check.src;
       document.images.military2.src = check.src;
       document.images.military3.src = check.src;
       document.images.military4.src = check.src;
       document.images.military5.src = box.src;
       document.images.military6.src = box.src;
       document.images.military7.src = box.src;
       document.images.military8.src = box.src;
       document.images.military9.src = box.src;
       document.images.military10.src = box.src;
     }

     else if(index == 50)
     {
       document.images.military1.src = check.src;
       document.images.military2.src = check.src;
       document.images.military3.src = check.src;
       document.images.military4.src = check.src;
       document.images.military5.src = check.src;
       document.images.military6.src = box.src;
       document.images.military7.src = box.src;
       document.images.military8.src = box.src;
       document.images.military9.src = box.src;
       document.images.military10.src = box.src;
     }

     else if(index == 60)
     {
       document.images.military1.src = check.src;
       document.images.military2.src = check.src;
       document.images.military3.src = check.src;
       document.images.military4.src = check.src;
       document.images.military5.src = check.src;
       document.images.military6.src = check.src;
       document.images.military7.src = box.src;
       document.images.military8.src = box.src;
       document.images.military9.src = box.src;
       document.images.military10.src = box.src;
     }

     else if(index == 70)
     {
       document.images.military1.src = check.src;
       document.images.military2.src = check.src;
       document.images.military3.src = check.src;
       document.images.military4.src = check.src;
       document.images.military5.src = check.src;
       document.images.military6.src = check.src;
       document.images.military7.src = check.src;
       document.images.military8.src = box.src;
       document.images.military9.src = box.src;
       document.images.military10.src = box.src;
     }

     else if(index == 80)
     {
       document.images.military1.src = check.src;
       document.images.military2.src = check.src;
       document.images.military3.src = check.src;
       document.images.military4.src = check.src;
       document.images.military5.src = check.src;
       document.images.military6.src = check.src;
       document.images.military7.src = check.src;
       document.images.military8.src = check.src;
       document.images.military9.src = box.src;
       document.images.military10.src = box.src;
     }

     else if(index == 90)
     {
       document.images.military1.src = check.src;
       document.images.military2.src = check.src;
       document.images.military3.src = check.src;
       document.images.military4.src = check.src;
       document.images.military5.src = check.src;
       document.images.military6.src = check.src;
       document.images.military7.src = check.src;
       document.images.military8.src = check.src;
       document.images.military9.src = check.src;
       document.images.military10.src = box.src;
     }

     else if(index == 100)
     {
       document.images.military1.src = check.src;
       document.images.military2.src = check.src;
       document.images.military3.src = check.src;
       document.images.military4.src = check.src;
       document.images.military5.src = check.src;
       document.images.military6.src = check.src;
       document.images.military7.src = check.src;
       document.images.military8.src = check.src;
       document.images.military9.src = check.src;
       document.images.military10.src = check.src;
     }
   }


  function factory_lock_toggle()
  {
    if(factory_locked == true)
    {
      factory_locked = false;
      document.images.factory_lock.src = unlock.src;
    }
    else
    {
      factory_locked = true;
      document.images.factory_lock.src = lock.src;
      laboratory_locked = false;
      document.images.laboratory_lock.src = unlock.src;
      military_locked = false;
      document.images.military_lock.src = unlock.src;
    }
  }


  function laboratory_lock_toggle()
  {
    if(laboratory_locked == true)
    {
      laboratory_locked = false;
      document.images.laboratory_lock.src = unlock.src;
    }
    else
    {
      laboratory_locked = true;
      document.images.laboratory_lock.src = lock.src;
      military_locked = false;
      document.images.military_lock.src = unlock.src;
      factory_locked = false;
      document.images.factory_lock.src = unlock.src;
    }
  }


  function military_lock_toggle()
  {
    if(military_locked == true)
    {
      military_locked = false;
      document.images.military_lock.src = unlock.src;
    }
    else
    {
      military_locked = true;
      document.images.military_lock.src = lock.src;
      factory_locked = false;
      document.images.factory_lock.src = unlock.src;
      laboratory_locked = false;
      document.images.laboratory_lock.src = unlock.src;
    }
  }


  function init(fac, lab, mil)
  {
    setFactory(fac);
    setLaboratory(lab);
    setMilitary(mil);
    factory_value = fac;
    laboratory_value = lab;
    military_value = mil;
  }
