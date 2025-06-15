
import React, { useState } from 'react';
import CalculatorDisplay from './CalculatorDisplay';
import CalculatorButton from './CalculatorButton';

const Calculator = () => {
  const [currentOperand, setCurrentOperand] = useState('0');
  const [previousOperand, setPreviousOperand] = useState<string | null>(null);
  const [operation, setOperation] = useState<string | null>(null);
  const [overwrite, setOverwrite] = useState(true);

  const calculate = () => {
    if (!previousOperand || !operation) return currentOperand;
    const prev = parseFloat(previousOperand);
    const current = parseFloat(currentOperand);
    if (isNaN(prev) || isNaN(current)) return 'Error';

    let computation: number;
    switch (operation) {
      case '+':
        computation = prev + current;
        break;
      case '-':
        computation = prev - current;
        break;
      case 'x':
        computation = prev * current;
        break;
      case '/':
        if (current === 0) return 'Error';
        computation = prev / current;
        break;
      default:
        return 'Error';
    }
    return computation.toString();
  };

  const handleNumberClick = (number: string) => {
    if (overwrite) {
      setCurrentOperand(number);
      setOverwrite(false);
    } else {
      if (number === '.' && currentOperand.includes('.')) return;
      if (number === '0' && currentOperand === '0') return;
      setCurrentOperand(currentOperand + number);
    }
  };

  const chooseOperation = (op: string) => {
    if (currentOperand === '' && previousOperand === null) {
      return
    }

    if (overwrite && previousOperand !== null) {
        setOperation(op);
        return;
    }

    if (previousOperand === null) {
      setPreviousOperand(currentOperand);
    } else {
      const result = calculate();
      setCurrentOperand(result);
      setPreviousOperand(result);
    }
    
    setOperation(op);
    setOverwrite(true);
  };

  const handleEqualsClick = () => {
    if (operation === null || previousOperand === null) return;
    const result = calculate();
    setCurrentOperand(result);
    setPreviousOperand(null);
    setOperation(null);
    setOverwrite(true);
  };

  const handleClearClick = () => {
    setCurrentOperand('0');
    setPreviousOperand(null);
    setOperation(null);
    setOverwrite(true);
  };
  
  const handleDeleteClick = () => {
    if (overwrite) {
      handleClearClick();
      return;
    }
    if (currentOperand.length === 1) {
      setCurrentOperand('0');
      setOverwrite(true);
      return;
    }
    setCurrentOperand(currentOperand.slice(0, -1));
  }


  return (
    <div className="w-80 bg-card rounded-2xl shadow-2xl p-4 space-y-4 border">
      <CalculatorDisplay value={currentOperand} />
      <div className="grid grid-cols-4 gap-2">
        <CalculatorButton onClick={handleClearClick} className="bg-secondary hover:bg-secondary/80">AC</CalculatorButton>
        <CalculatorButton onClick={handleDeleteClick} className="bg-secondary hover:bg-secondary/80">DEL</CalculatorButton>
        <CalculatorButton onClick={() => chooseOperation('/')} className="bg-primary text-primary-foreground hover:bg-primary/90">/</CalculatorButton>
        <CalculatorButton onClick={() => chooseOperation('x')} className="bg-primary text-primary-foreground hover:bg-primary/90">x</CalculatorButton>

        <CalculatorButton onClick={() => handleNumberClick('7')}>7</CalculatorButton>
        <CalculatorButton onClick={() => handleNumberClick('8')}>8</CalculatorButton>
        <CalculatorButton onClick={() => handleNumberClick('9')}>9</CalculatorButton>
        <CalculatorButton onClick={() => chooseOperation('-')} className="bg-primary text-primary-foreground hover:bg-primary/90">-</CalculatorButton>

        <CalculatorButton onClick={() => handleNumberClick('4')}>4</CalculatorButton>
        <CalculatorButton onClick={() => handleNumberClick('5')}>5</CalculatorButton>
        <CalculatorButton onClick={() => handleNumberClick('6')}>6</CalculatorButton>
        <CalculatorButton onClick={() => chooseOperation('+')} className="bg-primary text-primary-foreground hover:bg-primary/90">+</CalculatorButton>

        <CalculatorButton onClick={() => handleNumberClick('1')}>1</CalculatorButton>
        <CalculatorButton onClick={() => handleNumberClick('2')}>2</CalculatorButton>
        <CalculatorButton onClick={() => handleNumberClick('3')}>3</CalculatorButton>
        <CalculatorButton onClick={handleEqualsClick} className="row-span-2 bg-primary text-primary-foreground hover:bg-primary/90">=</CalculatorButton>
        
        <CalculatorButton onClick={() => handleNumberClick('0')} className="col-span-2">0</CalculatorButton>
        <CalculatorButton onClick={() => handleNumberClick('.')}>.</CalculatorButton>
      </div>
    </div>
  );
};

export default Calculator;
