
import React from 'react';
import { Button, ButtonProps } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface CalculatorButtonProps extends ButtonProps {}

const CalculatorButton: React.FC<CalculatorButtonProps> = ({ className, children, ...props }) => {
  return (
    <Button
      variant="outline"
      className={cn(
        'h-16 text-2xl font-semibold rounded-lg',
        'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
        'transition-transform duration-75 ease-out active:scale-95',
        'border-2',
        className
      )}
      {...props}
    >
      {children}
    </Button>
  );
};

export default CalculatorButton;
