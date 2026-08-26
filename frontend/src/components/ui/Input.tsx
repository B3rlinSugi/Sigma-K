import React from 'react';
import { cn } from '@/lib/utils';

export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
  helperText?: string;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
}

export const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ className, label, error, helperText, leftIcon, rightIcon, id, ...props }, ref) => {
    const inputId = id || (label ? label.toLowerCase().replace(/\s+/g, '-') : undefined);

    return (
      <div className="w-full">
        {label && (
          <label htmlFor={inputId} className="block text-xs font-semibold text-slate-700 mb-1">
            {label} {props.required && <span className="text-red-500">*</span>}
          </label>
        )}
        <div className="relative flex items-center">
          {leftIcon && <div className="absolute left-3 text-slate-400 pointer-events-none">{leftIcon}</div>}
          <input
            id={inputId}
            ref={ref}
            className={cn(
              'w-full h-9 px-3 text-sm bg-white border border-slate-300 rounded-md placeholder:text-slate-400 text-slate-900',
              'focus:outline-none focus:ring-2 focus:ring-primary-800 focus:border-transparent transition-all',
              'disabled:bg-slate-50 disabled:text-slate-500 disabled:cursor-not-allowed',
              leftIcon && 'pl-9',
              rightIcon && 'pr-9',
              error && 'border-red-500 focus:ring-red-500',
              className
            )}
            {...props}
          />
          {rightIcon && <div className="absolute right-3 text-slate-400">{rightIcon}</div>}
        </div>
        {error && <p className="text-xs text-red-600 mt-1">{error}</p>}
        {helperText && !error && <p className="text-xs text-slate-500 mt-1">{helperText}</p>}
      </div>
    );
  }
);
Input.displayName = 'Input';

export interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  error?: string;
  helperText?: string;
  options?: { value: string | number; label: string }[];
}

export const Select = React.forwardRef<HTMLSelectElement, SelectProps>(
  ({ className, label, error, helperText, options, children, id, ...props }, ref) => {
    const selectId = id || (label ? label.toLowerCase().replace(/\s+/g, '-') : undefined);

    return (
      <div className="w-full">
        {label && (
          <label htmlFor={selectId} className="block text-xs font-semibold text-slate-700 mb-1">
            {label} {props.required && <span className="text-red-500">*</span>}
          </label>
        )}
        <select
          id={selectId}
          ref={ref}
          className={cn(
            'w-full h-9 px-3 text-sm bg-white border border-slate-300 rounded-md text-slate-900',
            'focus:outline-none focus:ring-2 focus:ring-primary-800 focus:border-transparent transition-all',
            'disabled:bg-slate-50 disabled:text-slate-500',
            error && 'border-red-500 focus:ring-red-500',
            className
          )}
          {...props}
        >
          {options ? options.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          )) : children}
        </select>
        {error && <p className="text-xs text-red-600 mt-1">{error}</p>}
        {helperText && !error && <p className="text-xs text-slate-500 mt-1">{helperText}</p>}
      </div>
    );
  }
);
Select.displayName = 'Select';
