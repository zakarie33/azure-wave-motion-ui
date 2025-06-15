
import React from 'react';

interface CalculatorDisplayProps {
  value: string;
}

const CalculatorDisplay: React.FC<CalculatorDisplayProps> = ({ value }) => {
  return (
    <div className="bg-muted text-muted-foreground rounded-lg p-4 text-right text-5xl font-light break-all h-24 flex items-end justify-end">
      {value}
    </div>
  );
};

export default CalculatorDisplay;
